<?php


namespace ExtendedWoo\controllers;

use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class AbstractController
{
    protected Request $request;
    protected string $viewsDir;
    private array $steps;
    private string $currentStep;

    public function __construct(Request $request)
    {
        wp_deregister_script('wc-product-import');

        $this->request           = $request;
        $this->viewsDir = __DIR__ . '/../templates/';
        $this->steps = ['tets1', 'tets2'];
    }

    public function setSteps(array $steps): AbstractController
    {
        $this->steps = $steps;

        return $this;
    }

    public function setCurrentStep(string $step): AbstractController
    {
        $this->currentStep = $step;

        return $this;
    }

    public function render(string $filename, array $params = []): void
    {
        $params['steps'] = $this->steps;
        $twig = new Environment(new FilesystemLoader($this->viewsDir));

        echo $twig->render($filename, $params);
    }

    abstract public function index(): void;
}