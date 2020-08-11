<?php


namespace ExtendedWoo\Entities;

interface ContentInterface
{
    public function getTitle(): string;
    public function setTitle(string $title): ContentInterface;
    public function getExcerpt(): string;
    public function setExcerpt(string $excerpt): ContentInterface;
    public function getContent(): string;
    public function setContent(string $content): ContentInterface;
    public function generate(): ContentInterface;
}
