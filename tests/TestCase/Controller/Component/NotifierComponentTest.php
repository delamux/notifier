<?php
declare(strict_types=1);

namespace Bakkerij\Notifier\Test\TestCase\Controller\Component;

use Bakkerij\Notifier\Controller\Component\NotifierComponent;
use Bakkerij\Notifier\Model\Entity\Notification;
use Bakkerij\Notifier\Utility\NotificationManager;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * NotifierComponent tests.
 *
 * Users are UUID strings (as in CakeDC/Users) and integers, to cover both id types.
 */
class NotifierComponentTest extends TestCase
{
    private const ALICE = '6f1c0a44-6a8e-4c0b-9a0a-1a2b3c4d5e6f';
    private const BOB = '9d2e1b55-7b9f-4d1c-8b1b-2b3c4d5e6f70';

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Bakkerij/Notifier.Notifications',
    ];

    private NotifierComponent $Notifier;

    private NotificationManager $Manager;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Manager = NotificationManager::instance();
        $this->Notifier = $this->buildComponent(null);
    }

    /**
     * Builds the component for a controller whose request carries the given identity id.
     *
     * @param string|int|null $identityId Identifier of the logged in user, or null for none.
     * @return \Bakkerij\Notifier\Controller\Component\NotifierComponent
     */
    private function buildComponent(string|int|null $identityId): NotifierComponent
    {
        $request = new ServerRequest();
        if ($identityId !== null) {
            $request = $request->withAttribute('identity', new class ($identityId) {
                public function __construct(private string|int $id)
                {
                }

                public function getIdentifier(): string|int
                {
                    return $this->id;
                }
            });
        }

        return new NotifierComponent(new ComponentRegistry(new Controller($request)));
    }

    /**
     * Three notifications for Alice, two for Bob; one of Alice's and both of Bob's read.
     *
     * @return array<string, array<int>> Ids of the notifications per user.
     */
    private function seed(): array
    {
        $this->Manager->notify(['users' => [self::ALICE, self::ALICE, self::ALICE, self::BOB, self::BOB]]);
        $ids = [self::ALICE => [], self::BOB => []];
        foreach ($this->getTableLocator()->get('Bakkerij/Notifier.Notifications')->find()->orderBy(['id' => 'asc']) as $n) {
            if (isset($ids[$n->user_id])) {
                $ids[$n->user_id][] = (int)$n->id;
            }
        }
        $this->Notifier->markAsRead($ids[self::ALICE][0], self::ALICE);
        $this->Notifier->markAsRead($ids[self::BOB][0], self::BOB);
        $this->Notifier->markAsRead($ids[self::BOB][1], self::BOB);

        return $ids;
    }

    /**
     * @return void
     */
    public function testCountMethodsByState(): void
    {
        $this->seed();

        $this->assertSame(3, $this->Notifier->countAllNotifications(self::ALICE));
        $this->assertSame(2, $this->Notifier->countUnreadNotifications(self::ALICE));
        $this->assertSame(1, $this->Notifier->countReadNotifications(self::ALICE));

        $this->assertSame(2, $this->Notifier->countAllNotifications(self::BOB));
        $this->assertSame(0, $this->Notifier->countUnreadNotifications(self::BOB));
        $this->assertSame(2, $this->Notifier->countReadNotifications(self::BOB));
    }

    /**
     * @return void
     */
    public function testCountMethodsWithIntegerUserIds(): void
    {
        $this->Manager->notify(['users' => [1, 1, 2]]);

        $this->assertSame(2, $this->Notifier->countUnreadNotifications(1));
        $this->assertSame(1, $this->Notifier->countAllNotifications(2));
        $this->assertSame(0, $this->Notifier->countReadNotifications(2));
    }

    /**
     * @return void
     */
    public function testGetMethodsByState(): void
    {
        $ids = $this->seed();

        $this->assertCount(3, $this->Notifier->getAllNotificationsBy(self::ALICE));
        $unread = $this->Notifier->getUnReadNotificationsBy(self::ALICE);
        $this->assertCount(2, $unread);
        foreach ($unread as $notification) {
            $this->assertSame(Notification::UNREAD_STATUS, (int)$notification->state);
        }
        $read = $this->Notifier->getReadNotificationsBy(self::ALICE);
        $this->assertCount(1, $read);
        $this->assertSame($ids[self::ALICE][0], (int)$read[0]->id);

        $this->assertCount(0, $this->Notifier->getUnReadNotificationsBy(self::BOB));
    }

    /**
     * The state of the method wins over a state passed in the options.
     *
     * @return void
     */
    public function testExplicitStateOverridesStateInOptions(): void
    {
        $this->seed();

        $options = ['whereConditions' => ['state' => Notification::READ_STATUS]];
        $this->assertCount(2, $this->Notifier->getUnReadNotificationsBy(self::ALICE, $options));
        $this->assertCount(3, $this->Notifier->getAllNotificationsBy(self::ALICE, $options));
    }

    /**
     * Custom order is applied as an order clause (it was merged into the conditions before).
     *
     * @return void
     */
    public function testCustomOrder(): void
    {
        $ids = $this->seed();

        $asc = $this->Notifier->getAllNotificationsBy(self::ALICE, ['order' => ['Notifications.id' => 'asc']]);
        $this->assertSame($ids[self::ALICE], array_map(fn ($n) => (int)$n->id, $asc));
    }

    /**
     * @return void
     */
    public function testGetNotificationOnlyReturnsOwnNotifications(): void
    {
        $ids = $this->seed();

        $own = $this->Notifier->getNotification($ids[self::ALICE][1], self::ALICE);
        $this->assertInstanceOf(Notification::class, $own);
        $this->assertSame(self::ALICE, $own->user_id);

        $this->assertNull($this->Notifier->getNotification($ids[self::ALICE][1], self::BOB));
        $this->assertNull($this->Notifier->getNotification(999999, self::ALICE));
    }

    /**
     * Without an explicit user id, the identity of the request is used.
     *
     * @return void
     */
    public function testDefaultsToTheCurrentIdentity(): void
    {
        $ids = $this->seed();
        $notifier = $this->buildComponent(self::ALICE);

        $this->assertSame(2, $notifier->countUnreadNotifications());
        $this->assertCount(3, $notifier->getAllNotificationsBy());
        $this->assertNotNull($notifier->getNotification($ids[self::ALICE][1]));

        $this->assertTrue($notifier->markAsRead());
        $this->assertSame(0, $notifier->countUnreadNotifications());
        $this->assertSame(0, $this->Notifier->countReadNotifications(self::BOB) - 2, 'Bob is not affected');
    }

    /**
     * @return void
     */
    public function testMarkAsReadSingleNotification(): void
    {
        $ids = $this->seed();

        $this->assertTrue($this->Notifier->markAsRead($ids[self::ALICE][2], self::ALICE));
        $this->assertSame(1, $this->Notifier->countUnreadNotifications(self::ALICE));
        $this->assertSame(2, $this->Notifier->countReadNotifications(self::ALICE));
    }

    /**
     * The deprecated boolean/int API keeps its behaviour.
     *
     * @return void
     */
    public function testDeprecatedStateFlagMethodsKeepBehaviour(): void
    {
        $this->seed();

        $this->assertSame(3, $this->Notifier->countNotifications(self::ALICE));
        $this->assertSame(2, $this->Notifier->countNotifications(self::ALICE, true));
        $this->assertSame(2, $this->Notifier->countNotifications(self::ALICE, Notification::UNREAD_STATUS));
        $this->assertSame(1, $this->Notifier->countNotifications(self::ALICE, false));
        $this->assertSame(1, $this->Notifier->countNotifications(self::ALICE, Notification::READ_STATUS));

        $this->assertCount(3, $this->Notifier->getNotifications(self::ALICE));
        $this->assertCount(2, $this->Notifier->getNotifications(self::ALICE, true));
        $this->assertCount(1, $this->Notifier->getNotifications(self::ALICE, false));
    }
}
