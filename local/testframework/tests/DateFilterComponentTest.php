<?php
use PHPUnit\Framework\TestCase;
use \Bitrix\Main\Type\DateTime as DateTimeBitrix;

class DateFilterComponentTest extends TestCase
{
    protected $backupGlobals = false;
    protected $backupGlobalsBlacklist = ['DB'];
    public $dateFilterComponent;
    public $faker;

    public function setUp()
    {
        \CBitrixComponent::includeComponentClass("reinhurd:date.filter");
        // создание экземпляра Faker, который будет создавать рандомные данные
        $this->faker = \Faker\Factory::create();
        $this->dateFilterComponent = new DateFilterComponent();
        //заполняем фейковыми данными
        $this->dateFilterComponent->arParams = array(
            "IBLOCK_TYPE" => "news",
            "IBLOCK_ID" => "2",
            "FILTER_NAME" => "arrFilter",
            "CACHE_TIME" => "3600",
        );
    }

    public function tearDown()
    {
        // без этого вызова Mockery не будет работать
        \Mockery::close();
    }

    public function testCmodule()
    {
        $this->assertTrue(
            CModule::IncludeModule('iblock'),
            "Подключены ли вообще инфоблоки"
        );
    }

    public function testGetParamsAllDate()
    {
        $normal_params = array(
            'select' => array("ACTIVE_FROM"),
            'filter' => array(
                "IBLOCK_ID" => $this->dateFilterComponent->arParams['IBLOCK_ID']
            ),
            'cache' => array("ttl" => $this->dateFilterComponent->arParams['CACHE_TIME'])
        );
        $this->assertSame(
            $normal_params,
            $this->dateFilterComponent->getParamsAllDate(),
            "Корректная генерация параметров для запроса дат"
        );
    }

    public function testConvertGetToDateTime()
    {
        $test_raw_data = array(
            "YEAR" => 2012,
            "MONTH" => 10
        );
        $test_raw_all_dates_from_d7 = array(
            "2012" => array("10")
        );
        $result = $this->dateFilterComponent->convertGetToDateTime(
            $test_raw_data,
            $test_raw_all_dates_from_d7
        );
        $expected_result = array (
            "BEGIN" => new DateTimeBitrix("2012 10 01 00:00:01", "Y m d H:i:s"),
            "END" => new DateTimeBitrix("2012 10 31 23:59:59", "Y m d H:i:s")
        );
        $this->assertEquals(
            $expected_result,
            $result,
            "Массив для фильтрации формируется из объектов DateTimeBitrix");
    }

    public function testExecuteComponent()
    {
        //Проверка, не выброшено ли ошибок в процессе подключения компонента
        $this->dateFilterComponent->executeComponent();
        $this->assertTrue(true);
    }

    public function testGetParamsFilteredIdToDate()
    {
        $test_request_date = array (
            "BEGIN" => new DateTimeBitrix("2012 10 01 00:00:01", "Y m d H:i:s"),
            "END" => new DateTimeBitrix("2012 10 31 23:59:59", "Y m d H:i:s")
        );
        $normal_params = array(
            'select' => array("ID"),
            'filter' => array(
                "IBLOCK_ID" => $this->dateFilterComponent->arParams['IBLOCK_ID'],
                '>ACTIVE_FROM' => $test_request_date["BEGIN"],
                '<ACTIVE_FROM' => $test_request_date["END"]
            ),
            'order' => array('ACTIVE_FROM' => 'DESC'),
            'cache' => array("ttl" => $this->dateFilterComponent->arParams['CACHE_TIME'])
        );
        $this->assertSame(
            $normal_params,
            $this->dateFilterComponent->getParamsFilteredIdToDate($test_request_date),
            "Корректная генерация параметров для запроса ID"
        );
    }

    public function testGetGlobalVarRequest()
    {
        $this->assertIsObject(
            $this->dateFilterComponent->getGlobalVarRequest(),
            "Возвращение глобальных переменных методом битрикс"
        );
    }

    public function testNormalizeAllId()
    {
        // \Bitrix\Main\DB\Result - использование заглушки на этот класс
        // приводит к потере всей памяти

//        $DBResultStub = \Mockery::mock('CIBlockResult');
//        $stub_return = array("ID" => 2);
//        $DBResultStub->shouldReceive('fetch')->atLeast(1)
//            ->andReturn($stub_return);
        $test_request_date = array (
            "BEGIN" => new DateTimeBitrix("2012 10 01 00:00:01", "Y m d H:i:s"),
            "END" => new DateTimeBitrix("2012 10 31 23:59:59", "Y m d H:i:s")
        );
        $normal_params = array(
            'select' => array("ID"),
            'filter' => array(
                "IBLOCK_ID" => $this->dateFilterComponent->arParams['IBLOCK_ID'],
                '>ACTIVE_FROM' => $test_request_date["BEGIN"],
                '<ACTIVE_FROM' => $test_request_date["END"]
            ),
            'order' => array('ACTIVE_FROM' => 'DESC'),
            'cache' => array("ttl" => $this->dateFilterComponent->arParams['CACHE_TIME'])
        );
        $result = $this->dateFilterComponent->normalizeAllId(
            \Bitrix\Iblock\ElementTable::getList($normal_params)
        );
        $this->assertTrue(
            true,
            "Нет ошибок при работе метода"
        );
    }

    public function testCreateUriLinkForFilter()
    {
        $test_raw_date_from_global_get = array (
                2012 => array ( 0 => '02', 1 => '01', ),
                2010 => array ( 0 => '12', 1 => '11', ),
            );
        $test_uriString = '/news/';

        $expected_result = array (
            2012 => array (
                'YEAR_LINK' => '/news/?year=2012',
                'MONTHS' => array (
                    '02' => '/news/?year=2012&month=02',
                    '01' => '/news/?year=2012&month=01',
                    ),
                ),
            2010 => array ( 'YEAR_LINK' => '/news/?year=2010',
                'MONTHS' => array (
                    12 => '/news/?year=2010&month=12',
                    11 => '/news/?year=2010&month=11',
                    ),
                ),
            );
        $result = $this->dateFilterComponent->createUriLinkForFilter( $test_raw_date_from_global_get, $test_uriString);
        $this->assertEquals(
            $expected_result,
            $result,
            "Корректная генерация урл-ссылок для шаблона"
        );
    }

    public function testGetFilterParam()
    {
        // создание заглушки для класса \Bitrix\Main\HttpRequest
        $this->HttpRequestMock = \Mockery::mock('\Bitrix\Main\HttpRequest');

        $this->HttpRequestMock->shouldReceive('getQuery')->atLeast(4)
            ->andReturn('2012', '2012', '2012','2012', '10');
        $expectedResult = array(
            "YEAR" => "2012",
            "MONTH" => "10"
        );

        $answer = $this->dateFilterComponent->getFilterParam(
            $this->HttpRequestMock
        );
        $this->assertSame(
            $expectedResult,
            $answer,
            "Данные должны преобразовываться в массив"
        );
    }

    public function testGetFilterParamIncorrect()
    {
        // создание заглушки для класса \Bitrix\Main\HttpRequest
        $this->HttpRequestMock = \Mockery::mock('\Bitrix\Main\HttpRequest');

        $this->HttpRequestMock->shouldReceive('getQuery')->atLeast(1)
            ->andReturn('DAMNED_FAKE');

        $answer = $this->dateFilterComponent->getFilterParam(
            $this->HttpRequestMock
        );
        $this->assertFalse(
            $answer,
            "Некорректные данные не должны приводить к формированию массива");
    }

    public function testPrepareAndSetArrfilter()
    {
        $test_data = array(
            "YEAR" => 2012,
        );
        $this->dateFilterComponent->prepareAndSetArrfilter($test_data);
        $filter_name = $this->dateFilterComponent->arParams['FILTER_NAME'];
        global ${$filter_name};
        $test_arrfilter = ${$filter_name};
        $this->assertNotEmpty(
            $test_arrfilter,
            "Происходит генерация переменной фильтра"
        );
    }

    public function testGetAndClearUri()
    {
        $test_url = $this->faker->url;
        $test_url_norm = parse_url($test_url)["path"] . '?year=2012&months=10';
        $answer_url = $this->dateFilterComponent->getAndClearUri($test_url);
        $this->assertStringNotContainsString(
            "&months=10",
            $answer_url,
            "параметр месяца должен быть очищен"
        );
    }

    public function testGetInfoFromGetListD7()
    {
        $normal_params = array(
            'select' => array("ACTIVE_FROM"),
            'filter' => array(
                "IBLOCK_ID" => $this->dateFilterComponent->arParams['IBLOCK_ID']
            ),
            'cache' => array("ttl" => $this->dateFilterComponent->arParams['CACHE_TIME'])
        );
        $result = $this->dateFilterComponent->getInfoFromGetListD7($normal_params, "DATE");
        $this->assertIsArray(
            $result,
            "Метод возвращает массив"
        );
    }

    public function testGetLastDayOfMonth()
    {
        $test_month = '09';
        $test_year = '2019';
        $result = $this->dateFilterComponent->getLastDayOfMonth($test_month, $test_year);
        $expected_result = 30;

        $this->assertEquals(
            $expected_result,
            $result,
            'Корректное вычисление последнего числа месяца'
        );
    }
}
