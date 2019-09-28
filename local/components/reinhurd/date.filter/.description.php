<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    "NAME" => GetMessage("IBLOCK_FILTER_TEMPLATE_NAME"),
    "DESCRIPTION" => GetMessage("IBLOCK_FILTER_TEMPLATE_DESCRIPTION"),
    "SORT" => 10,
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "content",
        "CHILD" => array(
            "ID" => "news",
            "NAME" => GetMessage("T_IBLOCK_DESC_NEWSFILTER"),
            "SORT" => 10,
        )
    ),
);