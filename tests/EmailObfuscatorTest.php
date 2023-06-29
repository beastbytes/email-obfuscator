<?php
namespace BeastBytes\Widgets\EmailObfuscator\Tests;

use BeastBytes\Widgets\EmailObfuscator\EmailObfuscator;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\WebView;
use Yiisoft\Widget\WidgetFactory;

class EmailObfuscatorTest extends TestCase
{
    private const EMAIL_ADDRESS = 'test.email@example.com';

    private WebView $webView;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new SimpleContainer(
            [
                WebView::class => new WebView(__DIR__ . '/public/view', new SimpleEventDispatcher()),
            ]
        );

        WidgetFactory::initialize($container, []);

        $this->webView = $container
            ->get(WebView::class)
            ->withBasePath(__DIR__ . '/support/view')
        ;
    }

    public function test_no_email()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(EmailObfuscator::EMAIL_NOT_SET_EXCEPTION_MESSAGE);
        EmailObfuscator::widget()
                       ->render();
    }

    public function test_bad_email()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(EmailObfuscator::INVALID_EMAIL_EXCEPTION_MESSAGE);
        EmailObfuscator::widget()
            ->email('not_an_email_address');
    }

    public function test_base_obfuscation()
    {
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->render();

        $this->assertStringMatchesFormat(
            '<span id="' . EmailObfuscator::ID_PREFIX . '%d%d%d%d%d%d%d%d%d%d%d%d%d%d">'
            . EmailObfuscator::OBFUSCATED_CONTENT
            . '</span>',
            $content
        );

        $html = $this->webView->render( '/layout.php', ['content' => $content]);
        $matches = [];
        preg_match('/id="(email-obfuscator_\d+)"/', $html, $matches);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $matches[1],
            ]),
            $html
        );
    }

    public function test_obfuscated_content()
    {
        $obfusctedContent = 'Obfuscated Content';

        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->obfuscatedContent($obfusctedContent)
            ->render();

        $this->assertStringMatchesFormat(
            '<span id="' . EmailObfuscator::ID_PREFIX . '%d%d%d%d%d%d%d%d%d%d%d%d%d%d">'
            . $obfusctedContent
            . '</span>',
            $content
        );

        $html = $this->webView->render( '/layout.php', ['content' => $content]);
        $matches = [];
        preg_match('/id="(email-obfuscator_\d+)"/', $html, $matches);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $matches[1],
            ]),
            $html
        );
    }

    public function test_attributes()
    {
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->attributes(['class' => 'obfuscated'])
            ->render();

        $this->assertStringMatchesFormat(
            '<span'
            . ' id="' . EmailObfuscator::ID_PREFIX . '%d%d%d%d%d%d%d%d%d%d%d%d%d%d"'
            . ' class="obfuscated"'
            . '>'
            . EmailObfuscator::OBFUSCATED_CONTENT
            . '</span>',
            $content
        );

        $html = $this->webView->render( '/layout.php', ['content' => $content]);
        $matches = [];
        preg_match('/id="(email-obfuscator_\d+)"/', $html, $matches);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $matches[1],
            ]),
            $html
        );
    }

    public function test_id()
    {
        $id = 'eo-0';
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->id($id)
            ->render();

        $this->assertSame(
            '<span id="' . $id . '">'
            . EmailObfuscator::OBFUSCATED_CONTENT
            . '</span>',
            $content
        );

        $html = $this->webView->render( '/layout.php', ['content' => $content]);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $id,
            ]),
            $html
        );
    }

    public function test_tag()
    {
        $tag = 'div';
        $id = 'eo-0';
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->id($id)
            ->tag($tag)
            ->render();

        $this->assertSame(
            '<' . $tag . ' id="' . $id . '">'
            . EmailObfuscator::OBFUSCATED_CONTENT
            . '</' . $tag . '>',
            $content
        );

        $html = $this->webView->render( '/layout.php', ['content' => $content]);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $id,
            ]),
            $html
        );
    }

    public function test_obfuscators()
    {
        $obfuscators = [' dot ', ' at '];
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->obfuscators($obfuscators)
            ->render();

        $this->assertStringMatchesFormat(
            '<span'
            . ' id="' . EmailObfuscator::ID_PREFIX . '%d%d%d%d%d%d%d%d%d%d%d%d%d%d"'
            . ' title="' . EmailObfuscator::OBFUSCATED_CONTENT . '"'
            . '>'
            . str_replace(['.', '@'], $obfuscators, self::EMAIL_ADDRESS)
            . '</span>',
            $content
        );

        $html = $this->webView->render( '/layout.php', ['content' => $content]);
        $matches = [];
        preg_match('/id="(email-obfuscator_\d+)"/', $html, $matches);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $matches[1],
            ]),
            $html
        );
    }

    public function test_email_content()
    {
        $clearContent = 'Clear Content';
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->clearContent($clearContent)
            ->render();

        $html = $this->webView->render( '/layout.php', ['content' => $content]);
        $matches = [];
        preg_match('/id="(email-obfuscator_\d+)"/', $html, $matches);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => '',
                '{link}' => 'true',
                '{clearContent}' => $clearContent,
                '{emailAttributes}' => '[]',
                '{id}' => $matches[1],
            ]),
            $html
        );
    }

    public function test_email_subject()
    {
        $subject = 'Subject';
        $content = EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->emailAttributes(['subject' => $subject])
            ->render();

        $html = $this->webView->render( '/layout.php', ['content' => $content]);
        $matches = [];
        preg_match('/id="(email-obfuscator_\d+)"/', $html, $matches);

        $this->assertJsFunction($html);
        $this->assertStringContainsString(
            strtr(EmailObfuscator::JS_OBJECT, [
                '{name}' => "'test','email'",
                '{domain}' => "'example','com'",
                '{subject}' => $subject,
                '{link}' => 'true',
                '{clearContent}' => '',
                '{emailAttributes}' => '[]',
                '{id}' => $matches[1],
            ]),
            $html
        );
    }

    #[DataProvider('badObfuscatorsProvider')]
    public function test_bad_obfuscators($obfuscators)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(EmailObfuscator::OBFUSCATORS_EXCEPTION_MESSAGE);
        EmailObfuscator::widget()
            ->email(self::EMAIL_ADDRESS)
            ->obfuscators($obfuscators);
    }

    public static function badObfuscatorsProvider(): Generator
    {
        for ($i = 0; $i < 10; $i++) {
            if ($i !== 2) {
                yield [array_fill(0, $i, '!')];
            }
        }
    }

    private function assertJsFunction(string $html)
    {
        $this->assertStringContainsString(
            'const _' . md5(EmailObfuscator::class) . '='
            . EmailObfuscator::JS_FUNCTION,
            $html
        );
    }
}
