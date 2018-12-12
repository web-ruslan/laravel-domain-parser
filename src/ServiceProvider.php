<?php

/**
 * Laravel Domain Parser Package (https://github.com/bakame-php/laravel-domain-parser).
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\Laravel\Pdp;

use App;
use Closure;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Pdp\Rules;
use Pdp\TopLevelDomains;
use function config_path;
use function dirname;

final class ServiceProvider extends BaseServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__).'/config/domain-parser.php' => config_path('domain-parser'),
        ], 'config');

        if (App::runningInConsole()) {
            $this->commands([RefreshCacheCommand::class]);
        }

        $is_domain = [Constraints::class, 'isDomain'];
        $is_tld = [Constraints::class, 'isTLD'];
        $contains_tld = [Constraints::class, 'containsTLD'];
        $is_known_suffix = [Constraints::class, 'isKnownSuffix'];
        $is_icann_suffix = [Constraints::class, 'isICANNSuffix'];
        $is_private_suffix = [Constraints::class, 'isPrivateSuffix'];

        Blade::if('domain_name', Closure::fromCallable($is_domain));
        Blade::if('tld', Closure::fromCallable($is_tld));
        Blade::if('contains_tld', Closure::fromCallable($contains_tld));
        Blade::if('known_suffix', Closure::fromCallable($is_known_suffix));
        Blade::if('icann_suffix', Closure::fromCallable($is_icann_suffix));
        Blade::if('private_suffix', Closure::fromCallable($is_private_suffix));

        Validator::extend(
            'is_domain_name',
            Closure::fromCallable(new ValidatorWrapper($is_domain)),
            'The :attribute field is not a valid domain name.'
        );

        Validator::extend(
            'is_tld',
            Closure::fromCallable(new ValidatorWrapper($is_tld)),
            'The :attribute field is not a top level domain.'
        );

        Validator::extend(
            'contains_tld',
            Closure::fromCallable(new ValidatorWrapper($contains_tld)),
            'The :attribute field does end with a top level domain.'
        );

        Validator::extend(
            'is_known_suffix',
            Closure::fromCallable(new ValidatorWrapper($is_known_suffix)),
            'The :attribute field is not a domain with an known suffix.'
        );

        Validator::extend(
            'is_icann_suffix',
            Closure::fromCallable(new ValidatorWrapper($is_icann_suffix)),
            'The :attribute field is not a domain with an ICANN suffix.'
        );

        Validator::extend(
            'is_private_suffix',
            Closure::fromCallable(new ValidatorWrapper($is_private_suffix)),
            'The :attribute field iis not a domain with a private suffix.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/domain-parser.php', 'domain-parser');

        App::singleton('domain-rules', Closure::fromCallable([Adapter::class, 'getRules']));
        App::singleton('domain-toplevel', Closure::fromCallable([Adapter::class, 'getTLDs']));

        App::alias('domain-rules', Rules::class);
        App::alias('domain-toplevel', TopLevelDomains::class);
    }
}
