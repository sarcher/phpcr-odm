<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Tools\Helper\TranslationConverter;
use PHPCR\SessionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author David Buchmann <mail@davidbu.ch>
 */
class DocumentConvertTranslationCommand extends Command
{
    private $translationConverter;

    public function __construct($name = null, TranslationConverter $translationConverter = null)
    {
        parent::__construct($name);
        $this->translationConverter = $translationConverter;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:document:convert-translation')
            ->setDescription('Convert fields to translated or back to untranslated, and between different strategies after a refactoring.')

            ->addArgument('classname', InputArgument::REQUIRED, 'Class that has changed translation information')
            ->addOption('previous-strategy', 'prev', InputOption::VALUE_OPTIONAL,
                'Name of the previous translation strategy if there was one. Omit for converting from non-translated to translated'
            )
            ->addOption('fields', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'The fields to convert. If not specified, all fields configured as translated will be converted.',
                array()
            )
            ->setHelp(<<<HERE
The <info>doctrine:phpcr:docment:convert-translation</info> command migrates translations
from a previous format to the current mapping.

  <info>$ php ./app/console/phpcr doctrine:phpcr:document:convert-translation "Document\\ClassName"</info>

<comment>When some fields already where translated, you need to specify which fields to convert.</comment>
Failing to do that would erase all fields already translated previously.

Note that when only some fields changed or when converting between translation strategies, you need
to specify the previous strategy. When converting to untranslated, you additionally need to specify
the fields that previously where translated.
HERE
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $session SessionInterface */
        $session = $this->getHelper('phpcr')->getSession();
        $converter = $this->getTranslationConverter();
        $class = $input->getArgument('classname');
        $previous = $input->getOption('previous-strategy');
        $fields = $input->getOption('fields');

        while ($converter->convert($class, $previous, $fields)) {
            $session->save();
            $output->write('.');
        }

        $output->writeln('done');
    }

    /**
     * @return TranslationConverter
     */
    private function getTranslationConverter()
    {
        if (!$this->translationConverter) {
            $this->translationConverter = new TranslationConverter(
                $this->getHelper('phpcr')->getDocumentManager()
            );
        }

        return $this->translationConverter;
    }
}
