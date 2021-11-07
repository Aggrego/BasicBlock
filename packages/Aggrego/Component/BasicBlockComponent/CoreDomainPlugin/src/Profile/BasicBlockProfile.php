<?php
declare(strict_types=1);

namespace Aggrego\Component\BasicBlockComponent\CoreDomainPlugin\Profile;

use Aggrego\Component\BoardComponent\Domain\Board\Board;
use Aggrego\Component\BoardComponent\Domain\Board\Factory\Exception\UnprocessablePrototype;
use Aggrego\Component\BoardComponent\Domain\Board\Id\Id;
use Aggrego\Component\BoardComponent\Domain\Board\Name as BoardName;
use Aggrego\Component\BoardComponent\Domain\BoardPrototype\Metadata;
use Aggrego\Component\BoardComponent\Domain\BoardPrototype\Name as PrototypeName;
use Aggrego\Component\BoardComponent\Domain\BoardPrototype\Prototype;
use Aggrego\Component\BoardComponent\Domain\Profile\Building\BuildingProfile;
use Aggrego\Component\BoardComponent\Domain\Profile\Building\Exception\UnprocessableKeyChange;
use Aggrego\Component\BoardComponent\Domain\Profile\KeyChange;
use Aggrego\Component\BoardComponent\Domain\Profile\Name;
use Aggrego\Component\BoardComponent\Domain\Profile\Transformation\Exception\UnprocessableBoard;
use Aggrego\Component\BoardComponent\Domain\Profile\Transformation\TransformationProfile;
use Assert\Assertion;
use Assert\AssertionFailedException;
use TimiTao\ValueObject\Standard\Required\AbstractClass\ValueObject\ArrayValueObject;
use TimiTao\ValueObject\Standard\Required\AbstractClass\ValueObject\StringValueObject;

class BasicBlockProfile implements BuildingProfile, TransformationProfile
{
    public const KEY_UUID = 'uuid';
    public const KEY_NAME = 'name';
    public const KEY_VALUE = 'value';

    private const NAME = 'basic-block';

    public function __construct(
        private PrototypeName $prototypeName,
        private BoardName $boardName,
    ) {
    }


    public function getName(): Name
    {
        return Name::createFromParts(self::NAME, '1.0.0');
    }

    /**
     * @throws UnprocessableKeyChange
     */
    public function buildBoard(KeyChange $change): Prototype
    {
        $keyValue = $change->getValue();
        try {
            Assertion::keyExists($keyValue, self::KEY_UUID);
            Assertion::uuid($keyValue[self::KEY_UUID]);
            Assertion::keyExists($keyValue, self::KEY_NAME);
            Assertion::notEmpty($keyValue[self::KEY_NAME]);
            Assertion::keyExists($keyValue, self::KEY_VALUE);
            Assertion::notEmpty($keyValue[self::KEY_VALUE]);
        } catch (AssertionFailedException $e) {
            throw new UnprocessableKeyChange('Unable to create board due to: ' . $e->getMessage(), 0, $e);
        }

        $prototype = [];
        $prototype['profileName'] = (string)$this->getName();
        $prototype['prototypeName'] = $this->prototypeName->getValue();
        $prototype[self::KEY_UUID] = $keyValue[self::KEY_UUID];
        $prototype[self::KEY_NAME] = $keyValue[self::KEY_NAME];
        $prototype[self::KEY_VALUE] = $keyValue[self::KEY_VALUE];

        return new class($prototype) extends ArrayValueObject implements Prototype {
            public function getName(): PrototypeName
            {
                return new PrototypeName($this->getValue()['prototypeName']);
            }

            public function getProfileName(): Name
            {
                return Name::createFromName($this->getValue()['profileName']);
            }

            public function getMetadata(): Metadata
            {
                return new Metadata(
                    [
                        BasicBlockProfile::KEY_UUID => $this->getValue()[BasicBlockProfile::KEY_UUID],
                        BasicBlockProfile::KEY_NAME => $this->getValue()[BasicBlockProfile::KEY_NAME],
                        BasicBlockProfile::KEY_VALUE => $this->getValue()[BasicBlockProfile::KEY_VALUE],
                    ]
                );
            }

            public function hasParentId(): bool
            {
                return false;
            }

            public function getParentId(): Id
            {
                throw new \Exception('Not implemented');
            }
        };
    }

    /**
     * @throws UnprocessableBoard
     * @throws UnprocessablePrototype
     */
    public function transform(KeyChange $change, Board $board): Prototype
    {
        if (!$board->getBoardName()->equals($this->boardName)) {
            throw new UnprocessableBoard(
                sprintf(
                    'Expected board type "%s", but got "%s"',
                    $this->boardName->getValue(),
                    $board->getBoardName()->getValue()
                )
            );
        }

        $keyValue = $change->getValue();
        try {
            Assertion::keyExists($keyValue, self::KEY_VALUE);
        } catch (AssertionFailedException $e) {
            throw new UnprocessablePrototype('Unable to process board due to: ' . $e->getMessage(), 0, $e);
        }

        $prototype = [];
        $prototype['profileName'] = (string)$this->getName();
        $prototype['boardId'] = $board->getId()->getValue();
        $prototype['prototypeName'] = $this->prototypeName->getValue();
        $boardMetadata = $board->getMetadata()->getValue();
        $prototype[self::KEY_UUID] = $boardMetadata[self::KEY_UUID];
        $prototype[self::KEY_NAME] = $boardMetadata[self::KEY_NAME];
        $prototype[self::KEY_VALUE] = $change->getValue()[self::KEY_VALUE];

        return new class($prototype) extends ArrayValueObject implements Prototype {
            public function getName(): PrototypeName
            {
                return new PrototypeName($this->getValue()['prototypeName']);
            }

            public function getProfileName(): Name
            {
                return Name::createFromName($this->getValue()['profileName']);
            }

            public function getMetadata(): Metadata
            {
                return new Metadata(
                    [
                        BasicBlockProfile::KEY_UUID => $this->getValue()[BasicBlockProfile::KEY_UUID],
                        BasicBlockProfile::KEY_NAME => $this->getValue()[BasicBlockProfile::KEY_NAME],
                        BasicBlockProfile::KEY_VALUE => $this->getValue()[BasicBlockProfile::KEY_VALUE],
                    ]
                );
            }

            public function hasParentId(): bool
            {
                return true;
            }

            public function getParentId(): Id
            {
                return new class ($this->getValue()['boardId']) extends StringValueObject implements Id {
                };
            }
        };
    }
}
