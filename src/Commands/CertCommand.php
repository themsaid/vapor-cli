<?php

namespace Laravel\VaporCli\Commands;

use Laravel\VaporCli\Helpers;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CertCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('cert')
            ->addArgument('validation-method', InputArgument::REQUIRED, 'The certificate validation method (email, dns)')
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain name')
            ->addOption('add', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The additional domain names that should be added to the certificate', [])
            ->addOption('provider', null, InputOption::VALUE_OPTIONAL, 'The cloud provider ID')
            ->setDescription('Request a new SSL certificate');
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        Helpers::ensure_api_token_is_available();

        $domain = $this->argument('domain');

        $additionalDomains = $this->option('add');

        if (count(explode('.', $domain)) == 2) {
            $additionalDomains = array_unique(
                array_merge($additionalDomains, ['www.'.$domain])
            );
        }

        $this->vapor->requestCertificate(
            $this->determineProvider('Which cloud provider should the certificate belong to?'),
            $domain,
            $additionalDomains,
            $this->determineRegion('Which region should the certificate be placed in?'),
            $this->argument('validation-method')
        );

        Helpers::info('Certificate requested successfully for domains:');
        Helpers::line();

        $this->displayDomains($domain, $additionalDomains);

        Helpers::line();

        if ($this->argument('validation-method') == 'dns') {
            Helpers::line('Vapor will automatically add the DNS validation records to your zone.');
        } else {
            Helpers::line('You will receive a domain verification email at the following email addresses:');
            Helpers::line();

            $this->displayEmailAddresses($domain);

            Helpers::line();
            Helpers::line('Please approve the certificate by following the directions in the verification email.');
        }
    }

    /**
     * Display the certificate domains.
     *
     * @param  string  $domain
     * @param  array  $additionalDomains
     * @return void
     */
    protected function displayDomains($domain, array $additionalDomains)
    {
        foreach (array_merge([$domain], $additionalDomains) as $requested) {
            Helpers::comment(' - '.$requested);
        }
    }

    /**
     * Display the certificate verification email addresses.
     *
     * @param  string  $domain
     * @return void
     */
    protected function displayEmailAddresses($domain)
    {
        foreach (['administrator', 'hostmaster', 'postmaster', 'webmaster', 'admin'] as $address) {
            Helpers::comment(' - '.$address.'@'.$domain);
        }
    }
}
