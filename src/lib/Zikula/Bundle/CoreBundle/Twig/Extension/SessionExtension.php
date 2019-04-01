<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\CoreBundle\Twig\Extension;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zikula\Bundle\CoreBundle\Twig;

class SessionExtension extends AbstractExtension
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('showflashes', [$this, 'showFlashes'], ['is_safe' => ['html']])
        ];
    }

    /**
     * Display flash messages in twig template. Defaults to bootstrap alert classes.
     *
     * <pre>
     *  {{ showflashes() }}
     *  {{ showflashes({'class': 'custom-class', 'tag': 'span'}) }}
     * </pre>
     */
    public function showFlashes(array $params = []): string
    {
        $result = '';
        $total_messages = [];
        $messageTypeMap = [
            'error' => 'danger',
            'warning' => 'warning',
            'status' => 'success',
            'danger' => 'danger',
            'success' => 'success',
            'info' => 'info'
        ];

        foreach ($messageTypeMap as $messageType => $bootstrapClass) {
            $messages = $this->session->getFlashBag()->get($messageType);
            if (count($messages) > 0) {
                // Set class for the messages.
                $class = !empty($params['class']) ? $params['class'] : "alert alert-${bootstrapClass}";
                $total_messages += $messages;
                // Build output of the messages.
                if (empty($params['tag']) || ('span' !== $params['tag'])) {
                    $params['tag'] = 'div';
                }
                $result .= '<' . $params['tag'] . ' class="' . $class . '"';
                if (!empty($params['style'])) {
                    $result .= ' style="' . $params['style'] . '"';
                }
                $result .= '>';
                $result .= implode('<hr />', $messages);
                $result .= '</' . $params['tag'] . '>';
            }
        }

        if (empty($total_messages)) {
            return '';
        }

        return $result;
    }
}
