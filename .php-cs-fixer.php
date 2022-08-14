<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__])
    ->path("listeners/userpanel/SettingsListener.php")
    ->path("controllers/userpanel/Settings.php")
    ->path("controllers/Settings.php")
    ->path("libraries/validators/CheckoutLimitValidator.php");

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;