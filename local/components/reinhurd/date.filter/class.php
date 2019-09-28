<?php
use \Bitrix\Main\Loader,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Iblock\ElementTable,
    \Bitrix\Main\Application,
    \Bitrix\Main\Web\Uri,
    \Bitrix\Main\Web\HttpClient,
    \Bitrix\Main\Type\ParameterDictionary;
use \Bitrix\Main\Type\DateTime as DateTimeBitrix;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

class DateFilterComponent extends \CBitrixComponent
{
    public $raw_all_dates_from_d7;

    public function executeComponent():void
    {
        //вывод всех доступных дат
        $this->raw_all_dates_from_d7 = $this->getInfoFromGetListD7(
            $this->getParamsAllDate(),
            'DATE'
        );

        //подготовка массива ссылок для навигации
        $this->arResult["LINKS"] = $this->createUriLinkForFilter(
            $this->raw_all_dates_from_d7,
            $this->getGlobalVarRequest()->getRequestUri()
        );

        //работа с глобальной переменной для фильтрации в комплексном компоненте
        $this->workWithArrFilter();

        $this->includeComponentTemplate();
    }

    //есть ли гет-параметры для фильтрации по дате - если есть, фильтрует элементы инфоблока
    public function workWithArrFilter()
    {
        $arData = $this->getFilterParam($this->getGlobalVarRequest());
        if ($arData) {
            $this->prepareAndSetArrfilter($arData);
        }
    }
    //генерация uri сссылок на адреса фильтрации
    public function createUriLinkForFilter(array $raw_date_from_global_get, $uriString):array
    {
        $links_to_show = array();

        foreach ($raw_date_from_global_get as $year => $months) {

            $uri = $this->getAndClearUri($uriString);
            $uri->addParams(array("year" => $year));
            $redirect = $uri->getUri();
            $links_to_show[$year]['YEAR_LINK'] = $redirect;

            foreach ($months as $month) {
                $uri = $this->getAndClearUri($uriString);
                $uri->addParams(array("year" => $year, "month" => $month));
                $redirect = $uri->getUri();
                $links_to_show[$year]["MONTHS"][$month] = $redirect;
            }
        }
        return $links_to_show;
    }

    //доступ к суперглобальным переменным по d7
    public function getGlobalVarRequest()
    {
        return Application::getInstance()->getContext()->getRequest();
    }

    //Очистка страницы от фильтрации по месяцу, если выбран просто год
    public function getAndClearUri($uriString)
    {
        $uri = new Uri($uriString);
        $uri->deleteParams(array("month"));
        return $uri;
    }

    //Работа с заданной в комплексном компоненте переменной arrFilter, как бы она не называлась
    public function prepareAndSetArrfilter($arData):void
    {
        $arData_norm = $this->convertGetToDateTime($arData, $this->raw_all_dates_from_d7);
        $filter_name = $this->arParams['FILTER_NAME'];
        global ${$filter_name};
        ${$filter_name}['ID'] = $this->getInfoFromGetListD7($this->getParamsFilteredIdToDate($arData_norm), 'ID');
    }

    //Основной метод получения информации - запрос дат или id элементов
    public function getInfoFromGetListD7(array $parameters, string $date_or_id_choose):array
    {
        $result = array();

        $res = ElementTable::getList($parameters);
        if ($date_or_id_choose === 'DATE') {
            $result = $this->normalizeAllDate($res);
        } elseif ($date_or_id_choose === 'ID') {
            $result = $this->normalizeAllId($res);
        }
        return $result;
    }

    //подготовка arResult со всеми датами
    public function normalizeAllDate($res):array
    {
        $result = array();
        while ($item = $res->fetch()) {
            $item_raw = $item["ACTIVE_FROM"];
            if (is_object($item_raw)) {
                $item_year = $item_raw->format("Y");
                $item_month = $item_raw->format("m");
                if(!in_array($item_month, $result[$item_year])) {
                      $result[$item_year][] = $item_month;
                }
            }
        }
        return $result;
    }

    //подготовка ID для передачи в фильтр
    public function normalizeAllId($res):array
    {
        $result = array();
        while ($item = $res->fetch()) {
            $result[] = $item["ID"];
        }
        return $result;
    }

    //получение запроса по фильтрации из гет-параметров
    public function getFilterParam($request)
    {
        //Быстрая проверка корректности гет-параметров запроса в фильтр
        if (
            !$request->getQuery("year")
            || strlen($request->getQuery("year")) != 4
            || !is_numeric($request->getQuery("year"))
        ) {
            return false;
        }
        $year = htmlspecialchars($request->getQuery("year"));
        $month = htmlspecialchars($request->getQuery("month"));
        return array(
            "YEAR" => $year,
            "MONTH" => $month
        );
    }

    //параметры для получения всех дат
    public function getParamsAllDate():array
    {
        $parameters = array(
            'select' => array("ACTIVE_FROM"),
            'filter' => array(
                "IBLOCK_ID" => $this->arParams['IBLOCK_ID']
            ),
            'cache' => array("ttl" => $this->arParams['CACHE_TIME'])
        );

        return $parameters;
    }

    //параметры для запроса по фильтрованным по дате элементам
    public function getParamsFilteredIdToDate($request_date):array
    {
        $parameters = array(
            'select' => array("ID"),
            'filter' => array(
                "IBLOCK_ID" => $this->arParams['IBLOCK_ID'],
                '>ACTIVE_FROM' => $request_date["BEGIN"],
                '<ACTIVE_FROM' => $request_date["END"]
            ),
            'order' => array('ACTIVE_FROM' => 'DESC'),
            'cache' => array("ttl" => $this->arParams['CACHE_TIME'])
        );

        return $parameters;
    }

    //конвертация введенной даты в объект
    public function convertGetToDateTime($raw_date, $raw_all_dates_from_d7):array
    {
        $year = $raw_date["YEAR"];

        //Если введен неправильный месяц, запрос будет выполнен по всему году
        if (!in_array($raw_date['MONTH'], $raw_all_dates_from_d7[$year])) {
            $month_begin = "01";
            $month_end = "12";
        } else {
            $month_begin = $raw_date["MONTH"];
            $month_end = $raw_date["MONTH"];
        }

        $last_day = $this->getLastDayOfMonth($month_end, $year);

        return array (
            "BEGIN" => new DateTimeBitrix("$year $month_begin 01 00:00:01", "Y m d H:i:s"),
            "END" => new DateTimeBitrix("$year $month_end $last_day 23:59:59", "Y m d H:i:s")
        );
    }

    public function getLastDayOfMonth($month, $year)
    {
        $dateToTest = "$year-$month-01";
        return date('t',strtotime($dateToTest));
    }
}