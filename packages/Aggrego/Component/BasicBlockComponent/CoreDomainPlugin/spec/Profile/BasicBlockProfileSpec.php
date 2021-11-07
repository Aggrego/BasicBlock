<?php
/**
 *
 * This file is part of the Aggrego.
 * (c) Tomasz Kunicki <kunicki.tomasz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

declare(strict_types=1);

namespace spec\Aggrego\Component\BasicBlockComponent\CoreDomainPlugin\Profile;

use Aggrego\Component\BasicBlockComponent\CoreDomainPlugin\Profile\BasicBlockProfile;
use Aggrego\Component\BoardComponent\Domain\Board\Board;
use Aggrego\Component\BoardComponent\Domain\Board\Factory\Exception\UnprocessablePrototype;
use Aggrego\Component\BoardComponent\Domain\Board\Id\Id;
use Aggrego\Component\BoardComponent\Domain\Board\Metadata as BoardMetadata;
use Aggrego\Component\BoardComponent\Domain\Board\Name as BoardName;
use Aggrego\Component\BoardComponent\Domain\BoardPrototype\Name as PrototypeName;
use Aggrego\Component\BoardComponent\Domain\BoardPrototype\Prototype;
use Aggrego\Component\BoardComponent\Domain\BoardPrototype\Metadata as PrototypeMetadata;
use Aggrego\Component\BoardComponent\Domain\Profile\Building\BuildingProfile;
use Aggrego\Component\BoardComponent\Domain\Profile\Building\Exception\UnprocessableKeyChange;
use Aggrego\Component\BoardComponent\Domain\Profile\KeyChange;
use Aggrego\Component\BoardComponent\Domain\Profile\Transformation\TransformationProfile;
use PhpSpec\ObjectBehavior;

class BasicBlockProfileSpec extends ObjectBehavior
{
    function let(Board $board, Id $id)
    {
        $id->getValue()->willReturn('1');

        $boardName = new BoardName('test2');
        $this->beConstructedWith(
            new PrototypeName('test'),
            $boardName
        );
        $board->getBoardName()->willReturn($boardName);
        $board->getId()->willReturn($id);
        $board->getMetadata()->willReturn(
            new BoardMetadata([
                BasicBlockProfile::KEY_UUID => '7835a2f1-65c4-4e05-aacf-2e9ed950f5f2',
                BasicBlockProfile::KEY_NAME => 'test2',
                BasicBlockProfile::KEY_VALUE => 'value'
            ])
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(BuildingProfile::class);
        $this->shouldBeAnInstanceOf(TransformationProfile::class);
    }

    function it_should_generate_building_prototype()
    {
        $key = new KeyChange([BasicBlockProfile::KEY_NAME => 'test', BasicBlockProfile::KEY_VALUE => 'value', BasicBlockProfile::KEY_UUID => '7835a2f1-65c4-4e05-aacf-2e9ed950f5f2']);
        $result = $this->buildBoard($key);

        $result->shouldBeAnInstanceOf(Prototype::class);
        $result->getProfileName()->equal($this->getName())->shouldReturn(true);
        $result->getName()->equals(new PrototypeName('test'))->shouldReturn(true);

        $expectedPrototypeMetadata = new PrototypeMetadata([
            BasicBlockProfile::KEY_UUID => '7835a2f1-65c4-4e05-aacf-2e9ed950f5f2',
            BasicBlockProfile::KEY_NAME => 'test',
            BasicBlockProfile::KEY_VALUE => 'value',
        ]);
        $result->getMetadata()->shouldBeLike($expectedPrototypeMetadata);
        $result->getMetadata()->equals($expectedPrototypeMetadata)->shouldReturn(true);
    }

    function it_should_throw_exception_with_invalid_key_name()
    {
        $key = new KeyChange([BasicBlockProfile::KEY_VALUE => 'new_test_value', BasicBlockProfile::KEY_UUID => '7835a2f1-65c4-4e05-aacf-2e9ed950f5f2']);
        $unableToBuildBoardException = new UnprocessableKeyChange('Unable to create board due to: Array does not contain an element with key "name"');
        $this->shouldThrow($unableToBuildBoardException)
            ->during('buildBoard', [$key]);
    }

    function it_should_throw_exception_with_invalid_key_value_when_building()
    {
        $key = new KeyChange([BasicBlockProfile::KEY_NAME => 'test', BasicBlockProfile::KEY_UUID => '7835a2f1-65c4-4e05-aacf-2e9ed950f5f2']);
        $unableToBuildBoardException = new UnprocessableKeyChange('Unable to create board due to: Array does not contain an element with key "value"');
        $this->shouldThrow($unableToBuildBoardException)
            ->during('buildBoard', [$key]);
    }

    function it_should_generate_transformation_prototype(Board $board)
    {
        $key = new KeyChange([BasicBlockProfile::KEY_VALUE => 'new_test_value']);

        $result = $this->transform($key, $board);
        $result->shouldBeAnInstanceOf(Prototype::class);
        $result->getProfileName()->equal($this->getName())->shouldReturn(true);
        $result->getName()->equals(new PrototypeName('test'))->shouldReturn(true);
        $expectedPrototypeMetadata = new PrototypeMetadata([
            BasicBlockProfile::KEY_UUID => '7835a2f1-65c4-4e05-aacf-2e9ed950f5f2',
            BasicBlockProfile::KEY_NAME => 'test2',
            BasicBlockProfile::KEY_VALUE => 'new_test_value',
        ]);
        $result->getMetadata()->shouldBeLike($expectedPrototypeMetadata);
        $result->getMetadata()->equals($expectedPrototypeMetadata)->shouldReturn(true);
    }

    function it_should_throw_exception_with_invalid_key_value_when_transforming(Board $board)
    {
        $key = new KeyChange([]);
        $unableToBuildBoardException = new UnprocessablePrototype('Unable to process board due to: Array does not contain an element with key "value"');
        $this->shouldThrow($unableToBuildBoardException)
            ->during('transform', [$key, $board]);
    }
}
