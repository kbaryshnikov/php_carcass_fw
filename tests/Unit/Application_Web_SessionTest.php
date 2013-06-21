<?php

use \Carcass\Application;
use \Carcass\Corelib;

class Application_Web_SessionTest extends PHPUnit_Framework_TestCase {

    public function testStart() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $this->assertNull($Session->getSessionId());
        $Session->start();
        $sid = $Session->getSessionId();
        $this->assertNotEmpty($sid);
        $this->assertInternalType('string', $sid);
        $this->assertRegExp(Application\Web_Session::SESSION_ID_REGEXP, $sid);
    }

    public function testStartWithCustomSid() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $this->assertNull($Session->getSessionId());
        $Session->start($sid = '12345678901234567890ab');
        $this->assertEquals($sid, $Session->getSessionId());
    }

    public function testStartWithIncorrectSidThrows() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $this->assertNull($Session->getSessionId());
        $this->setExpectedException('\InvalidArgumentException');
        $Session->start('wrong');
    }

    public function testSetSessionId() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $Session->setSessionId($sid = '12346578901234567890ab');
        $this->assertEquals($sid, $Session->getSessionId());
    }

    public function testSetIncorrectSessionIdThrows() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $this->setExpectedException('\InvalidArgumentException');
        $Session->setSessionId('wrong');
    }

    public function testDestroyResetsDataAndSessionId() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $Session->set('x', 1);
        $Session->destroy();
        $this->assertNull($Session->getSessionId());
        $this->assertEmpty($Session->exportArray());
    }

    public function testGetIdentifierReturnsSidArrayByDefault() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $sid = $Session->getSessionId();
        $id = $Session->getIdentifier();
        $expected = [Application\Web_Session::DEFAULT_COOKIE_NAME => $sid];
        $this->assertEquals($expected, $id);
    }

    public function testGetIdentifierReturnsEmptyArrayWhenUserAgentSupportsCookies() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $Session->setUserAgentSupportsCookies(true);
        $id = $Session->getIdentifier();
        $this->assertSame([], $id);
    }

    public function testGetIdentifierReturnsEmptyArrayWhenSendingIdentifiersToUserAgentIsDisabled() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $Session->disableSendingSessionIdentifiersToUserAgent();
        $id = $Session->getIdentifier();
        $this->assertSame([], $id);
    }

    public function testGetIdentifierForceArgumentOverridesUserAgentStatusFlags() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Session = new Application\Web_Session($RequestMock, $ResponseMock);
        $Session->start();
        $Session->setUserAgentSupportsCookies(true);
        $id = $Session->getIdentifier();
        $expected = [];
        $this->assertEquals($expected, $id);
    }

    public function testReadingSessionDataFromStorageOnSessionStart() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $sid = '01234567890123456789ab';

        $SessionStorageMock
            ->expects($this->once())
            ->method('get')
            ->with($sid)
            ->will(
                $this->returnValue(
                    $session_data = [
                        'x' => 'value_of_x',
                        'y' => 'value_of_y',
                    ]
                )
            );

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->start($sid);

        $this->assertEquals('value_of_x', $Session->get('x'));
        $this->assertEquals('value_of_y', $Session->get('y'));
        $this->assertNull($Session->get('undefined'));
        $this->assertSame($session_data, $Session->exportArray());
    }

    public function testReadingSessionDataFromStorageOnSessionSetId() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $sid = '01234567890123456789ab';

        $SessionStorageMock
            ->expects($this->at(0))
            ->method('get')
            ->with(
                $this->matchesRegularExpression(Application\Web_Session::SESSION_ID_REGEXP)
            )
            ->will(
                $this->returnValue([])
            );

        $SessionStorageMock
            ->expects($this->at(1))
            ->method('get')
            ->with($sid)
            ->will(
                $this->returnValue(
                    $session_data = [
                        'x' => 'value_of_x',
                        'y' => 'value_of_y',
                    ]
                )
            );

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->start();
        $this->assertSame([], $Session->exportArray());

        $Session->setSessionId($sid);

        $this->assertEquals('value_of_x', $Session->get('x'));
        $this->assertEquals('value_of_y', $Session->get('y'));
        $this->assertNull($Session->get('undefined'));
        $this->assertSame($session_data, $Session->exportArray());
    }

    public function testWritingSessionDataToStorageOnSessionSave() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $sid = '01234567890123456789ab';

        $SessionStorageMock
            ->expects($this->at(0))
            ->method('get')
            ->with($sid)
            ->will(
                $this->returnValue(
                    $session_data = [
                        'x' => 'value_of_x',
                        'y' => 'value_of_y',
                    ]
                )
            );

        $new_session_data = [
            'x' => 'value_of_x',
            'y' => 'new_value_of_y',
            'z' => 'new_value_of_z',
        ];

        $SessionStorageMock
            ->expects($this->at(1))
            ->method('write')
            ->with($sid, $new_session_data, $has_changes = true)
            ->will(
                $this->returnSelf()
            );

        $SessionStorageMock
            ->expects($this->at(2))
            ->method('write')
            ->with($sid, $new_session_data, $has_changes = false)
            ->will(
                $this->returnSelf()
            );

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->start($sid);
        $Session->set('y', 'new_value_of_y');
        $Session->set('z', 'new_value_of_z');
        $Session->save();
        $Session->save();
    }

    public function testDeletingSessionDataFromStorageOnSessionDestroy() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $sid = '01234567890123456789ab';

        $SessionStorageMock
            ->expects($this->once())
            ->method('get')
            ->with($sid)
            ->will($this->returnValue([]));
        $SessionStorageMock
            ->expects($this->once())
            ->method('delete')
            ->with($sid)
            ->will($this->returnSelf());

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->start($sid);
        $Session->destroy();
    }

    public function testBindCallIsSentToStorage() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $uid = '123';
        $sid = '01234567890123456789ab';

        $SessionStorageMock
            ->expects($this->once())
            ->method('get')
            ->with($sid)
            ->will($this->returnValue([]));
        $SessionStorageMock
            ->expects($this->once())
            ->method('setBoundSid')
            ->with($uid, $sid)
            ->will($this->returnSelf());

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->start($sid);
        $Session->bind($uid);
    }

    public function testUnbindCallIsSentToStorage() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $uid = '123';

        $SessionStorageMock
            ->expects($this->once())
            ->method('setBoundSid')
            ->with($uid, null)
            ->will($this->returnSelf());

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->unbind($uid);
    }

    public function testIsBoundToLoadsReturnsWhetherUidIsBoundToSession() {
        /** @var Corelib\Request $RequestMock */
        $RequestMock = $this->getMock('\Carcass\Corelib\Request');
        /** @var Application\Web_Response $ResponseMock */
        $ResponseMock = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();

        $SessionStorageMock = $this->getMock('\Carcass\Application\Web_Session_StorageInterface');

        $uid = '123';
        $sid = '01234567890123456789ab';

        $SessionStorageMock
            ->expects($this->once())
            ->method('get')
            ->with($sid)
            ->will($this->returnValue([]));
        $SessionStorageMock
            ->expects($this->once())
            ->method('getBoundSid')
            ->with($uid)
            ->will(
                $this->returnCallback(
                    function ($_uid) use ($uid, $sid) {
                        return $uid === $_uid ? $sid : null;
                    }
                )
            );

        /** @var Application\Web_Session_StorageInterface $SessionStorageMock */
        $Session = new Application\Web_Session($RequestMock, $ResponseMock, $SessionStorageMock);
        $Session->start($sid);
        $Session->bind($uid);
        $this->assertTrue($Session->isBoundTo($uid));
    }

}