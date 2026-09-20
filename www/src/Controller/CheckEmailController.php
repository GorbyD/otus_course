<?php

namespace Controller;

use EmailValidator;
use Http\Request;
use Http\Response;

final class CheckEmailController
{
    public function __construct(
        private readonly EmailValidator $validator,
    ) {
    }

    public function handle(Request $request): Response
    {
        $raw = $request->post['emails'] ?? null;

        if ($raw === null || trim((string) $raw) === '') {
            return Response::text(
                "Bad Request: необходим параметр 'emails' (список строк, каждая на новой строке).\n",
                400,
            );
        }

        $lines = preg_split('/[\r\n,;]+/', $raw);
        $results = $this->validator->validateList($lines);

        if ($results === []) {
            return Response::text("Bad Request: список пуст.\n", 400);
        }

        $output = [];
        foreach ($results as $result) {
            $output[] = $result['valid']
                ? "OK: {$result['email']}"
                : "NOT OK: {$result['email']} ({$result['reason']})";
        }

        return Response::text(implode("\n", $output) . "\n", 200);
    }
}
