<?php

namespace Laravel\VaporCli;

use Symfony\Component\Process\Process;

class Docker
{
    /**
     * Build a docker image.
     *
     * @param  string  $path
     * @param  string  $project
     * @param  string  $environment
     * @return void
     */
    public static function build($path, $project, $environment)
    {
        Process::fromShellCommandline(
            sprintf('docker build --file=%s.Dockerfile --tag=%s .',
                $environment,
                $project.':'.$environment
            ),
            $path
        )->setTimeout(null)->mustRun(function ($type, $line) {
            Helpers::write($line);
        });
    }

    /**
     * Build a docker image.
     *
     * @param  string  $path
     * @param  string  $project
     * @param  string  $environment
     * @param  string  $token
     * @param  string  $repoUri
     * @param  string  $name
     * @return void
     */
    public static function publish($path, $project, $environment, $token, $repoUri, $name)
    {
        Process::fromShellCommandline(
            sprintf('docker tag %s %s',
                $project.':'.$environment,
                $repoUri.':'.$name
            ),
            $path
        )->setTimeout(null)->mustRun();

        Process::fromShellCommandline(
            sprintf('docker login --username AWS --password %s %s',
                str_replace('AWS:', '', base64_decode($token)),
                explode('/', $repoUri)[0]
            ),
            $path
        )->setTimeout(null)->mustRun();

        Process::fromShellCommandline(
            sprintf('docker push %s',
                $repoUri.':'.$name
            ),
            $path
        )->setTimeout(null)->mustRun(function ($type, $line) {
            Helpers::write($line);
        });
    }
}
