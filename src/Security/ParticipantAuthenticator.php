<?php

namespace App\Security;

use App\Entity\Participant;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ParticipantAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private ManagerRegistry $managerRegistry;

    public function __construct(private UrlGeneratorInterface $urlGenerator, ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->urlGenerator = $urlGenerator;
    }


    public function authenticate(Request $request): Passport
    {
        $emailOrPseudo = $request->request->get('emailOuPseudo', '');
        $isEmail = str_contains($emailOrPseudo, '@');
        $request->getSession()->set(Security::LAST_USERNAME, $emailOrPseudo);

        return new Passport(
            new UserBadge($emailOrPseudo, fn () => $this->loadUserByUsername($emailOrPseudo, $isEmail)),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    private function loadUserByUsername(string $identifier, bool $isEmail): Participant
    {
        $participantRepository = $this->managerRegistry->getRepository(Participant::class);
        // Trouver le participant par email ou pseuso suivant le $isEmail
        if ($isEmail) {
            $participant = $participantRepository->findOneBy(['email' => $identifier]);
        } else {
            $participant = $participantRepository->findOneBy(['pseudo' => $identifier]);
        }
        // Si aucun participant trouvé
        if (!$participant) {
            throw new CustomUserMessageAuthenticationException('Identifiant / MDP incorrect(s).');
        }

        return $participant;
    }



    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        return new RedirectResponse($this->urlGenerator->generate('app_sortie_accueil'));// à changer
        // throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
