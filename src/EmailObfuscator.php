<?php
/**
 * @copyright Copyright (c) 2022 BeastBytes - All Rights Reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Widgets\EmailObfuscator;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Widget\Widget;

/**
 * Obfuscates an email address to help prevent harvesting by spambots.
 *
 * If JavaScript is enabled the email address is reconstructed and by default shown as a mailto link.
 * If JavaScript is not enabled email address can be replaced with an obfuscated address or text or tooltip informing
 * users that the email address is hidden
 * @author Chris Yates
 */
final class EmailObfuscator extends Widget
{
    public const EMAIL_NOT_SET_EXCEPTION_MESSAGE = '"email" must be set using the "email()" setter';
    public const ID_PREFIX = 'email-obfuscator_';
    public const INVALID_EMAIL_EXCEPTION_MESSAGE = 'Invalid email address';
    public const JS_FUNCTION = "function(q){"
        . "const a=document.getElementById(q.z)"
        . "const b=q.t.join('.')+'@'+q.u.join('.')"
        . "if(q.w){"
        . "const c=document.createElement('a')"
        . "c.href='mailto:'+b+(q.v===''?'':'?subject='+q.v)"
        . "for(let d in q.x){c.setAttribute(d, q.x[d])}"
        . "c.appendChild(document.createTextNode(q.y===''?b:q.y))"
        . "a.innerHTML=c.outerHTML"
        . "}else{"
        . "a.innerHTML=b"
        . "}"
        . "}"
    ;
    public const JS_OBJECT = '{'
        . 't:new Array({name}),'
        . 'u:new Array({domain}),'
        . 'v:"{subject}",'
        . 'w:{link},'
        . 'x:{emailAttributes},'
        . 'y:"{clearContent}",'
        . 'z:"{id}"'
        . '}'
    ;
    public const OBFUSCATED_CONTENT = 'This e-mail address is protected';
    public const OBFUSCATORS_EXCEPTION_MESSAGE = 'Obfuscators must be a two element array';

    private const EMAIL_REGEX = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
    private const EMAIL_SEPARATORS = ['.', '@'];

    private array $attributes = [];
    private string $clearContent = '';
    private string $email = '';
    private array $emailAttributes = [];
    private string $obfuscatedContent = self::OBFUSCATED_CONTENT;
    private array $obfuscators = [];
    private string $tag = 'span';

    public function __construct(private WebView $webView)
    {
    }

    /**
     * Returns a new instance with the HTML attributes.
     *
     * @param array $valuesMap Attribute values indexed by attribute names.
     */
    public function attributes(array $valuesMap): self
    {
        $new = clone $this;
        $new->attributes = $valuesMap;

        return $new;
    }

    /**
     * Returns a new instance with the specified clear content.
     * If clearContent is not set, the clear content is the email address in the clear
     *
     * @param string $value The clear content.
     */
    public function clearContent(string $value): self
    {
        $new = clone $this;
        $new->clearContent = $value;

        return $new;
    }

    /**
     * Returns a new instance with the specified email address.
     *
     * @param string $value The email address.
     */
    public function email(string $value): self
    {
        if (preg_match(self::EMAIL_REGEX, $value) !== 1) {
            throw new InvalidArgumentException(self::INVALID_EMAIL_EXCEPTION_MESSAGE);
        }

        $new = clone $this;
        $new->email = $value;

        return $new;
    }

    /**
     * Returns a new instance with the HTML attributes for the email.
     * Two extra attributes are recognised:
     *
     * @param array $valuesMap Attribute values indexed by attribute names.
     */
    public function emailAttributes(array $valuesMap): self
    {
        $new = clone $this;
        $new->emailAttributes = $valuesMap;

        return $new;
    }

    /**
     * Returns a new instance with the specified Widget ID.
     *
     * @param string $value The id of the widget.
     */
    public function id(string $value): self
    {
        $new = clone $this;
        $new->attributes['id'] = $value;

        return $new;
    }

    /**
     * Returns a new instance with the specified obfuscated content.
     * If obfuscators are set the obfuscated content is the obfuscated email address and obfuscatedContent
     * the title attribute of the tag.
     *
     * @param string $value The obfuscated content.
     */
    public function obfuscatedContent(string $value): self
    {
        $new = clone $this;
        $new->obfuscatedContent = $value;

        return $new;
    }

    /**
     * Returns a new instance with the specified obfuscators.
     * The value is a two element array; the first value replaces '.' and the second replaces '@' in the email address.
     * If this is set the obfuscated email is the tag content and obfuscatedContent becomes the title attribute.
     *
     * @param array $value The obfuscators.
     */
    public function obfuscators(array $value): self
    {
        if (count($value) !== 2) {
            throw new InvalidArgumentException(self::OBFUSCATORS_EXCEPTION_MESSAGE);
        }

        $new = clone $this;
        $new->obfuscators = $value;

        return $new;
    }

    /**
     * Returns a new instance with the specified tag.
     *
     * @param string $value The tag name.
     */
    public function tag(string $value): self
    {
        $new = clone $this;
        $new->tag = $value;

        return $new;
    }

    public function render(): string
    {
        if ($this->email === '') {
            throw new RuntimeException(self::EMAIL_NOT_SET_EXCEPTION_MESSAGE);
        }

        if (!array_key_exists('id', $this->attributes)) {
            $this->attributes['id'] = Html::generateId(self::ID_PREFIX);
        }

        $this->registerJs($this->attributes['id']);

        if (!empty($this->obfuscators)) {
            $content = str_replace(self::EMAIL_SEPARATORS, $this->obfuscators, $this->email);
            $attributes = array_merge($this->attributes, ['title' => $this->obfuscatedContent]);
        } else {
            $content = $this->obfuscatedContent;
            $attributes = $this->attributes;
        }

        return Html::tag($this->tag, $content, $attributes)->render();
    }

    private function registerJs(string $id): void
    {
        $functionName = '_' . md5(self::class);

        // only one function on the page
        $this->webView->registerJs(
            'const ' . $functionName . '=' . self::JS_FUNCTION,
            WebView::POSITION_HEAD
        );

        $email = explode('@', $this->email);
        $name = explode('.', $email[0]);
        $domain = explode('.', $email[1]);

        foreach ($name as &$value) {
            $value = "'$value'";
        }
        unset($value);
        foreach ($domain as &$value) {
            $value = "'$value'";
        }
        unset($value);

        $link = ArrayHelper::remove($this->emailAttributes, 'link', true) ? 'true' : 'false';
        $subject = addcslashes(
            ArrayHelper::remove($this->emailAttributes, 'subject', ''),
            '"'
        );

        // One call to the function for each widget
        $this->webView->registerJs(
            $functionName . '('
            . strtr(self::JS_OBJECT, [
                '{name}' => implode(',', $name),
                '{domain}' => implode(',', $domain),
                '{subject}' => $subject,
                '{link}' => $link,
                '{clearContent}' => $this->clearContent,
                '{emailAttributes}' => json_encode($this->emailAttributes, JSON_THROW_ON_ERROR),
                '{id}' => $this->attributes['id'],
            ])
            . ')'
        );
    }
}
