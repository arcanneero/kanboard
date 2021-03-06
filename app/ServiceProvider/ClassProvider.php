<?php

namespace ServiceProvider;

use Core\ObjectStorage\FileStorage;
use Core\Paginator;
use Core\OAuth2;
use Core\Tool;
use Model\Config;
use Model\Project;
use Model\Webhook;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use League\HTMLToMarkdown\HtmlConverter;

class ClassProvider implements ServiceProviderInterface
{
    private $classes = array(
        'Model' => array(
            'Acl',
            'Action',
            'Authentication',
            'Board',
            'Category',
            'Color',
            'Comment',
            'Config',
            'Currency',
            'DateParser',
            'File',
            'LastLogin',
            'Link',
            'Notification',
            'Project',
            'ProjectActivity',
            'ProjectAnalytic',
            'ProjectDuplication',
            'ProjectDailyColumnStats',
            'ProjectDailyStats',
            'ProjectIntegration',
            'ProjectPermission',
            'Subtask',
            'SubtaskExport',
            'SubtaskForecast',
            'SubtaskTimeTracking',
            'Swimlane',
            'Task',
            'TaskAnalytic',
            'TaskCreation',
            'TaskDuplication',
            'TaskExport',
            'TaskFinder',
            'TaskFilter',
            'TaskLink',
            'TaskModification',
            'TaskPermission',
            'TaskPosition',
            'TaskStatus',
            'TaskValidator',
            'Timetable',
            'TimetableDay',
            'TimetableExtra',
            'TimetableWeek',
            'TimetableOff',
            'Transition',
            'User',
            'UserSession',
            'Webhook',
        ),
        'Core' => array(
            'EmailClient',
            'Helper',
            'HttpClient',
            'Lexer',
            'MemoryCache',
            'Request',
            'Router',
            'Session',
            'Template',
        ),
        'Integration' => array(
            'BitbucketWebhook',
            'GithubWebhook',
            'GitlabWebhook',
            'HipchatWebhook',
            'Jabber',
            'Mailgun',
            'Postmark',
            'Sendgrid',
            'SlackWebhook',
            'Smtp',
        )
    );

    public function register(Container $container)
    {
        Tool::buildDIC($container, $this->classes);

        $container['paginator'] = $container->factory(function ($c) {
            return new Paginator($c);
        });

        $container['oauth'] = $container->factory(function ($c) {
            return new OAuth2($c);
        });

        $container['htmlConverter'] = function($c) {
            return new HtmlConverter(array('strip_tags' => true));
        };

        $container['objectStorage'] = function($c) {
            return new FileStorage(FILES_DIR);
        };
    }
}
