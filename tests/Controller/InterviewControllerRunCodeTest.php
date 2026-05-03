<?php

namespace App\Tests\Controller;

use App\Controller\InterviewController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class InterviewControllerRunCodeTest extends TestCase
{
    public function testRunCodeEmptyCode(): void
    {
        $controller = new InterviewController();
        $controller->setContainer($this->getMockedContainer());

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'php',
            'code' => ''
        ]));

        /** @var JsonResponse $response */
        $response = $controller->runCode($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Code vide.', $data['error']);
    }

    public function testRunCodePhpSuccess(): void
    {
        $controller = new InterviewController();
        $controller->setContainer($this->getMockedContainer());

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'php',
            'code' => '<?php echo "Hello from test"; '
        ]));

        /** @var JsonResponse $response */
        $response = $controller->runCode($request);
        
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Hello from test', $data['output']);
        $this->assertEmpty($data['error']);
    }

    public function testRunCodeUnsupportedLanguage(): void
    {
        $controller = new InterviewController();
        $controller->setContainer($this->getMockedContainer());

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'cobol',
            'code' => 'Hello'
        ]));

        /** @var JsonResponse $response */
        $response = $controller->runCode($request);
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Langage non supporté.', $data['error']);
    }

    private function getMockedContainer(): Container
    {
        $container = new Container();
        
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        // Expect true for IS_AUTHENTICATED_FULLY
        $authChecker->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $container->set('security.authorization_checker', $authChecker);
        
        return $container;
    }
}
