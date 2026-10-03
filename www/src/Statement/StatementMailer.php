<?php

namespace Statement;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Письмо пользователю с выпиской во вложении.
 */
final class StatementMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $from,
    ) {
    }

    public static function createFromEnv(): self
    {
        $dsn = getenv('MAILER_DSN');

        return new self(
            new Mailer(Transport::fromDsn($dsn)),
            getenv('MAIL_FROM'),
        );
    }

    public function send(StatementRequest $request, string $csv): void
    {
        $text = sprintf(
            'Выписка за период %s — %s готова (запрос %s).',
            $request->dateFrom,
            $request->dateTo,
            $request->id,
        );

        $email = (new Email())
            ->from($this->from)
            ->to($request->email)
            ->subject('Ваша банковская выписка')
            ->text($text)
            ->attach($csv, "statement_{$request->dateFrom}_{$request->dateTo}.csv", 'text/csv');

        $this->mailer->send($email);
    }
}
