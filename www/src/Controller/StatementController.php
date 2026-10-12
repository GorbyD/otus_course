<?php

namespace Controller;

use EmailValidator;
use Http\Request;
use Http\Response;
use Queue\MessagePublisherInterface;
use Queue\QueueException;
use Statement\StatementRequest;

final class StatementController
{
    private const FORM = <<<'HTML'
        <!doctype html>
        <html lang="ru"><head><meta charset="utf-8"><title>Банковская выписка</title></head>
        <body>
        <h1>Заказ банковской выписки</h1>
        <form method="post" action="/statement">
            <p><label>Дата начала <input type="date" name="date_from" required></label></p>
            <p><label>Дата окончания <input type="date" name="date_to" required></label></p>
            <p><label>Email для уведомления <input type="email" name="email" required></label></p>
            <button type="submit">Запросить выписку</button>
        </form>
        </body></html>
        HTML;

    public function __construct(
        private readonly EmailValidator $emailValidator,
        private readonly MessagePublisherInterface $publisher,
    ) {
    }

    public function form(Request $request): Response
    {
        return Response::html(self::FORM);
    }

    public function submit(Request $request): Response
    {
        $email = trim((string) ($request->post['email'] ?? ''));
        $dateFrom = (string) ($request->post['date_from'] ?? '');
        $dateTo = (string) ($request->post['date_to'] ?? '');

        $error = $this->validate($email, $dateFrom, $dateTo);
        if ($error !== null) {
            return Response::html("Bad Request: {$error}\n", 400);
        }

        $statementRequest = StatementRequest::create($email, $dateFrom, $dateTo);

        try {
            $this->publisher->publish($statementRequest->toArray());
        } catch (QueueException) {
            return Response::html("Сервис временно недоступен, попробуйте позже.\n", 503);
        }

        return Response::html(
            "Запрос {$statementRequest->id} принят в обработку. "
            . "Результат будет отправлен на {$email}.\n",
            202,
        );
    }

    private function validate(string $email, string $dateFrom, string $dateTo): ?string
    {
        if (!$this->emailValidator->isValidSyntax($email)) {
            return "некорректный  'email'.";
        }

        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);
        if ($from === null || $to === null) {
            return "'date_from' и 'date_to' должны быть датами в формате YYYY-MM-DD.";
        }

        if ($from > $to) {
            return "'date_from' не может быть позже 'date_to'.";
        }

        return null;
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
