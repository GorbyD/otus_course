<?php

namespace Unit;

use EmailValidator;
use PHPUnit\Framework\TestCase;

final class EmailValidatorTest extends TestCase
{
    /**
     * @dataProvider syntaxProvider
     */
    public function testIsValidSyntax(string $email, bool $expected): void
    {
        $validator = new EmailValidator();

        self::assertSame($expected, $validator->isValidSyntax($email));
    }

    public static function syntaxProvider(): array
    {
        return [
            'plain email' => ['user@example.com', true],
            'with dots and plus' => ['user.name+tag@sub.example.co', true],
            'no at sign' => ['not-an-email', false],
            'no local part' => ['@example.com', false],
            'no domain' => ['user@', false],
            'no tld' => ['user@example', false],
            'space in local part' => ['user example@example.com', false],
            'empty string' => ['', false],
        ];
    }

    public function testHasMxRecordUsesInjectedCheckerWithExtractedDomain(): void
    {
        $seenDomain = null;
        $validator = new EmailValidator(function (string $domain) use (&$seenDomain): bool {
            $seenDomain = $domain;

            return true;
        });

        self::assertTrue($validator->hasMxRecord('user@example.com'));
        self::assertSame('example.com', $seenDomain);
    }

    public function testHasMxRecordReturnsFalseWhenCheckerReturnsFalse(): void
    {
        $validator = new EmailValidator(static fn (string $domain): bool => false);

        self::assertFalse($validator->hasMxRecord('user@example.com'));
    }


    public function testIsValidReturnsTrueWhenSyntaxIsValidAndMxRecordExists(): void
    {
        $validator = new EmailValidator(static fn (): bool => true);

        self::assertTrue($validator->isValid('user@example.com'));
    }

    public function testIsValidReturnsFalseWhenMxRecordIsMissing(): void
    {
        $validator = new EmailValidator(static fn (): bool => false);

        self::assertFalse($validator->isValid('user@example.com'));
    }




    public function testValidateListSkipsBlankLinesAndTrimsWhitespace(): void
    {
        $validator = new EmailValidator(static fn (): bool => true);

        $results = $validator->validateList(["  \n", '', "  user@example.com  \n"]);

        self::assertCount(1, $results);
        self::assertSame('user@example.com', $results[0]['email']);
        self::assertTrue($results[0]['valid']);
        self::assertNull($results[0]['reason']);
    }

    public function testValidateListReportsSyntaxErrorReason(): void
    {
        $validator = new EmailValidator(static fn (): bool => true);

        $results = $validator->validateList(['not-an-email']);

        self::assertSame([
            'email' => 'not-an-email',
            'valid' => false,
            'reason' => 'некорректный формат',
        ], $results[0]);
    }

    public function testValidateListReportsMissingMxRecordReason(): void
    {
        $validator = new EmailValidator(static fn (): bool => false);

        $results = $validator->validateList(['user@example.com']);

        self::assertSame([
            'email' => 'user@example.com',
            'valid' => false,
            'reason' => 'домен не принимает почту (нет MX-записи)',
        ], $results[0]);
    }

    public function testValidateListReturnsEmptyArrayForEmptyInput(): void
    {
        $validator = new EmailValidator();

        self::assertSame([], $validator->validateList([]));
    }

    public function testValidateListHandlesMultipleLinesIndependently(): void
    {
        $validator = new EmailValidator(
            static fn (string $domain): bool => $domain === 'good.com',
        );

        $results = $validator->validateList([
            'valid@good.com',
            'broken email',
            'valid@bad.com',
        ]);

        self::assertCount(3, $results);
        self::assertTrue($results[0]['valid']);
        self::assertFalse($results[1]['valid']);
        self::assertSame('некорректный формат', $results[1]['reason']);
        self::assertFalse($results[2]['valid']);
        self::assertSame('домен не принимает почту (нет MX-записи)', $results[2]['reason']);
    }
}
