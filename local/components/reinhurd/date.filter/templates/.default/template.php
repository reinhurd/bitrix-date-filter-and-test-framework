<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$this->setFrameMode(true);

?>
<table>
    <?foreach($arResult["LINKS"] as $year_key => $months_on_year):?>
        <tr>
            <td><a href="<?=$arResult["LINKS"][$year_key]["YEAR_LINK"]?>"><?=$year_key?></a></td>
                <?foreach($months_on_year["MONTHS"] as $month_num => $month_link):?>
                    <td><a href="<?=$month_link?>"><?=Loc::getMessage("DATEFILTER_MONTH_$month_num")?></a></td>
                <?endforeach;?>
        </tr>
    <?endforeach;?>
</table>
