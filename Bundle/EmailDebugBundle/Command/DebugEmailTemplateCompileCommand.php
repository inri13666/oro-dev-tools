<?php

namespace Gorgo\Bundle\EmailDebugBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DebugEmailTemplateCompileCommand extends ContainerAwareCommand
{
    const NAME = 'gorgo:debug:email:template:compile';

    /**
     * {@internal doc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Renders given email template')
            ->addOption(
                'template',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of email template to be compiled.'
            )
            ->addOption(
                'params-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to YML file with required params for compilation.'
            )
            ->addOption(
                'entity-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'An entity ID.'
            )
            ->addOption(
                'recipient',
                null,
                InputOption::VALUE_OPTIONAL,
                'Recipient email address. [Default: null]',
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $templateName = $input->getOption('template');
        $template = $this->getRepository()->findByName($templateName);
        if (!$template) {
            $output->writeln(sprintf('Template "%s" not found', $templateName));

            return 1;
        }
        $params = $this->getNormalizedParams($input->getOption('params-file'));

        if ($template->getEntityName()) {
            $params['entity'] = $this->getEntity($template->getEntityName(), $input->getOption('entity-id'));
        }

        $subject = $this->getEmailRenderer()->renderWithDefaultFilters($template->getSubject(), $params);
        $body = $this->getEmailRenderer()->renderWithDefaultFilters($template->getContent(), $params);

        if (!$input->getOption('recipient')) {
            $output->writeln(sprintf('SUBJECT: %s', $subject));
            $output->writeln('');
            $output->writeln('BODY:');
            $output->writeln($body);
        } else {
            $emailMessage = new \Swift_Message(
                $subject,
                $body,
                $template->getType() === 'html' ? 'text/html' : null
            );

            $emailMessage->setFrom($input->getOption('recipient'));
            $emailMessage->setTo($input->getOption('recipient'));

            try {
                $this->getMailer()->processSend($emailMessage, null);
                $output->writeln(sprintf('Message successfully send to "%s"', $input->getOption('recipient')));
            } catch (\Swift_SwiftException $e) {
                $output->writeln(sprintf('Message not sent due error "%s"', $e->getMessage()));
            }
        }

        return 0;
    }

    /**
     * @return ObjectRepository|EmailTemplateRepository
     */
    private function getRepository()
    {
        return $this->getDoctrineHelper()->getEntityRepositoryForClass(EmailTemplate::class);
    }

    /**
     * @return Processor
     */
    private function getMailer()
    {
        return $this->getContainer()->get('oro_email.mailer.processor');
    }

    /**
     * @return EmailRenderer
     */
    private function getEmailRenderer()
    {
        return $this->getContainer()->get('oro_email.email_renderer');
    }

    /**
     * @return DoctrineHelper
     */
    private function getDoctrineHelper()
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper');
    }

    /**
     * @param string $paramsFile
     *
     * @return array
     */
    private function getNormalizedParams($paramsFile)
    {
        if (is_file($paramsFile) && is_readable($paramsFile)) {
            return Yaml::parse(file_get_contents($paramsFile));
        }

        return [];
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @return object
     */
    private function getEntity($entityClass, $entityId = null)
    {
        $entity = $this->getDoctrineHelper()->createEntityInstance($entityClass);
        if ($entityId) {
            $entity = $this->getDoctrineHelper()->getEntity($entityClass, $entityId) ?: $entity;
        }

        return $entity;
    }
}
