<?php


namespace ExtendedWoo\controllers\import;


use ExtendedWoo\controllers\AbstractController;

class PrimaryImportController extends AbstractController
{
    public function mapping(): void
    {
        
    }

    public function index(): void
    {
        $this->render('import/import_form.html.twig');
    }
}