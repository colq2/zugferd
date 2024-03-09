<?php

/**
 * This file is a part of horstoeko/zugferd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferd;

use Exception;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use horstoeko\stringmanagement\PathUtils;
use horstoeko\zugferd\entities\en16931\rsm\CrossIndustryInvoiceType;
use horstoeko\zugferd\jms\ZugferdTypesHandler;
use horstoeko\zugferd\ZugferdObjectHelper;
use horstoeko\zugferd\ZugferdProfileResolver;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;

/**
 * Class representing the document basics
 *
 * @category Zugferd
 * @package  Zugferd
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferd
 */
class ZugferdDocument
{
    /**
     * @internal
     * @var      integer    Internal profile id
     */
    private $profileId = -1;

    /**
     * @internal
     * @var      array  Internal profile definition
     */
    private $profileDefinition = [];

    /**
     * @internal
     * @var      SerializerBuilder  Serializer builder
     */
    private $serializerBuilder;

    /**
     * @internal
     * @var      SerializerInterface    Serializer
     */
    private $serializer;

    /**
     * @internal
     * @var      CrossIndustryInvoiceType   The internal invoice object
     */
    protected $invoiceObject = null;

    /**
     * @internal
     * @var      ZugferdObjectHelper    Object Helper
     */
    protected $objectHelper = null;

    /**
     * Constructor
     *
     * @param integer $profile
     * The ID of the profile of the document
     *
     * @codeCoverageIgnore
     */
    protected function __construct(int $profile)
    {
        $this->initProfile($profile);
        $this->initObjectHelper();
        $this->initSerialzer();
    }

    /**
     * @internal
     *
     * Returns the internal invoice object (created by the
     * serializer). This is used e.g. in the validator
     *
     * @return object
     */
    public function getInvoiceObject()
    {
        return $this->invoiceObject;
    }

    /**
     * Get the instance of the internal serializuer
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Returns the selected profile id
     *
     * @return integer
     */
    public function getProfileId(): int
    {
        return $this->profileId;
    }

    /**
     * Returns the profile definition
     *
     * @return array
     */
    public function getProfileDefinition(): array
    {
        return $this->profileDefinition;
    }

    /**
     * Get a parameter from profile definition
     *
     * @param string $parameterName
     * @return mixed
     */
    public function getProfileDefinitionParameter(string $parameterName)
    {
        $profileDefinition = $this->getProfileDefinition();

        if (is_array($profileDefinition) && isset($profileDefinition[$parameterName])) {
            return $profileDefinition[$parameterName];
        }

        throw new Exception(sprintf("Unknown profile definition parameter %s", $parameterName));
    }

    /**
     * @internal
     *
     * Sets the internal profile definitions
     *
     * @param integer $profile
     * The internal id of the profile
     *
     * @return ZugferdDocument
     */
    private function initProfile(int $profile): ZugferdDocument
    {
        $this->profileId = $profile;
        $this->profileDefinition = ZugferdProfileResolver::resolveProfileDefById($profile);

        return $this;
    }

    /**
     * @internal
     *
     * Build the internal object helper
     * @codeCoverageIgnore
     *
     * @return ZugferdDocument
     */
    private function initObjectHelper(): ZugferdDocument
    {
        $this->objectHelper = new ZugferdObjectHelper($this->profileId);

        return $this;
    }

    /**
     * @internal
     *
     * Build the internal serialzer
     * @codeCoverageIgnore
     *
     * @return ZugferdDocument
     */
    private function initSerialzer(): ZugferdDocument
    {
        $this->serializerBuilder = SerializerBuilder::create();

        $this->serializerBuilder->addMetadataDir(
            PathUtils::combineAllPaths(
                ZugferdSettings::getYamlDirectory(),
                $this->getProfileDefinitionParameter("name"),
                'qdt'
            ),
            sprintf(
                'horstoeko\zugferd\entities\%s\qdt',
                $this->getProfileDefinitionParameter("name")
            )
        );
        $this->serializerBuilder->addMetadataDir(
            PathUtils::combineAllPaths(
                ZugferdSettings::getYamlDirectory(),
                $this->getProfileDefinitionParameter("name"),
                'ram'
            ),
            sprintf(
                'horstoeko\zugferd\entities\%s\ram',
                $this->getProfileDefinitionParameter("name")
            )
        );
        $this->serializerBuilder->addMetadataDir(
            PathUtils::combineAllPaths(
                ZugferdSettings::getYamlDirectory(),
                $this->getProfileDefinitionParameter("name"),
                'rsm'
            ),
            sprintf(
                'horstoeko\zugferd\entities\%s\rsm',
                $this->getProfileDefinitionParameter("name")
            )
        );
        $this->serializerBuilder->addMetadataDir(
            PathUtils::combineAllPaths(
                ZugferdSettings::getYamlDirectory(),
                $this->getProfileDefinitionParameter("name"),
                'udt'
            ),
            sprintf(
                'horstoeko\zugferd\entities\%s\udt',
                $this->getProfileDefinitionParameter("name")
            )
        );

        $this->serializerBuilder->addDefaultListeners();
        $this->serializerBuilder->addDefaultHandlers();

        $this->serializerBuilder->configureHandlers(
            function (HandlerRegistryInterface $handler) {
                $handler->registerSubscribingHandler(new BaseTypesHandler());
                $handler->registerSubscribingHandler(new XmlSchemaDateHandler());
                $handler->registerSubscribingHandler(new ZugferdTypesHandler());
            }
        );

        $this->serializer = $this->serializerBuilder->build();

        return $this;
    }
}
