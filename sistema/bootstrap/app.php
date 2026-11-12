<?php

declare(strict_types=1);

/**
 * Composition root: the only place that knows which implementation backs each
 * port. Returns the configured container for HTTP and CLI entry points.
 */

use App\Application\Catalog\ProcedureQueries;
use App\Application\Clinical\MedicalRecordQueries;
use App\Application\Clinical\PatientQueries;
use App\Application\Clinical\ProfessionalQueries;
use App\Application\Identity\AccountQueries;
use App\Application\Identity\GoogleIdentityProvider;
use App\Application\Identity\LoginCodeStore;
use App\Application\Identity\OAuthStateStore;
use App\Application\Identity\SessionPolicy;
use App\Application\Scheduling\AppointmentQueries;
use App\Application\Shared\RateLimiter;
use App\Application\Shared\Transaction;
use App\Domain\Audit\AuditTrail;
use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Clinical\MedicalRecordRepository;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Clinical\ProfessionalRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\PasswordResetRepository;
use App\Domain\Identity\SessionRepository;
use App\Domain\Identity\UserRepository;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Shared\Clock;
use App\Http\Kernel;
use App\Http\Router;
use App\Infrastructure\Config;
use App\Infrastructure\Container;
use App\Infrastructure\Persistence as P;
use App\Infrastructure\Security\BcryptPasswordHasher;
use App\Infrastructure\SystemClock;
use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    // Immutable: real environment variables win over .env (used by the tests).
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$config = new Config();
date_default_timezone_set($config->timezone());

$container = new Container();
$container->set(Config::class, fn (): Config => $config);
$container->set(P\Db::class, fn (Container $c): P\Db => P\Db::connect($c->get(Config::class)));
$container->set(SessionPolicy::class, fn (Container $c): SessionPolicy => new SessionPolicy(
    $c->get(Config::class)->int('SESSION_LIFETIME_HOURS', 12),
    $c->get(Config::class)->int('SESSION_IDLE_MINUTES', 120),
));

foreach ([
    Clock::class => SystemClock::class,
    Transaction::class => P\PdoTransaction::class,
    RateLimiter::class => P\PdoRateLimiter::class,
    PasswordHasher::class => BcryptPasswordHasher::class,
    AuditTrail::class => P\PdoAuditTrail::class,
    GoogleIdentityProvider::class => \App\Infrastructure\Google\GoogleOAuthClient::class,
    OAuthStateStore::class => P\PdoOAuthStateStore::class,
    LoginCodeStore::class => P\PdoLoginCodeStore::class,
    UserRepository::class => P\PdoUserRepository::class,
    SessionRepository::class => P\PdoSessionRepository::class,
    PasswordResetRepository::class => P\PdoPasswordResetRepository::class,
    PatientRepository::class => P\PdoPatientRepository::class,
    ProfessionalRepository::class => P\PdoProfessionalRepository::class,
    ProcedureRepository::class => P\PdoProcedureRepository::class,
    AppointmentRepository::class => P\PdoAppointmentRepository::class,
    MedicalRecordRepository::class => P\PdoMedicalRecordRepository::class,
    AccountQueries::class => P\Queries\PdoAccountQueries::class,
    AppointmentQueries::class => P\Queries\PdoAppointmentQueries::class,
    PatientQueries::class => P\Queries\PdoPatientQueries::class,
    MedicalRecordQueries::class => P\Queries\PdoMedicalRecordQueries::class,
    ProfessionalQueries::class => P\Queries\PdoProfessionalQueries::class,
    ProcedureQueries::class => P\Queries\PdoProcedureQueries::class,
] as $port => $adapter) {
    $container->bind($port, $adapter);
}

$container->set(Router::class, function (Container $c): Router {
    $router = new Router($c);
    require dirname(__DIR__) . '/routes/api.php';

    return $router;
});
$container->set(Kernel::class, fn (Container $c): Kernel => new Kernel(
    $c->get(Router::class),
    $c->get(\App\Http\ErrorResponder::class),
    $c->get(Config::class),
    dirname(__DIR__) . '/public/index.html',
));

return $container;
