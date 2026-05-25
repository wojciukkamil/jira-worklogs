<?php

declare(strict_types=1);

namespace App\Request;

use App\Exception\CalendarExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

interface RequestParserInterface
{
    /**
     * Parse and validate query parameters from a request.
     *
     * @throws CalendarExceptionInterface when validation fails
     */
    public function parse(Request $request): QueryParameters;
}
