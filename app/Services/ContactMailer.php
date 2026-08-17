<?php

declare(strict_types=1);

namespace App\Services;

interface ContactMailer
{
    /** @param array{name:string,email:string,phone:string,message:string,submitted_at:string,product:?array} $inquiry */
    public function send(array $inquiry): void;
}
