<?php

class Mailer
{
    public static function send(string $to, string $subject, string $message, ?string $replyTo = null): array
    {
        $headers = ['MIME-Version: 1.0', 'Content-type: text/plain; charset=UTF-8'];
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $delivered = false;
        if (function_exists('mail')) {
            $delivered = @mail($to, $subject, $message, implode("\r\n", $headers));
        }

        self::log($to, $subject, $message, $replyTo, $delivered);

        return [
            'sent' => $delivered,
            'logged' => true,
        ];
    }

    private static function log(string $to, string $subject, string $message, ?string $replyTo, bool $delivered): void
    {
        $directory = dirname(MAIL_LOG_FILE);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $entry = sprintf(
            "[%s] delivered=%s to=%s replyTo=%s subject=%s%s%s%s",
            date('Y-m-d H:i:s'),
            $delivered ? 'yes' : 'no',
            $to,
            $replyTo ?: '-',
            $subject,
            PHP_EOL,
            $message,
            PHP_EOL . str_repeat('-', 60) . PHP_EOL
        );

        file_put_contents(MAIL_LOG_FILE, $entry, FILE_APPEND);
    }
}
