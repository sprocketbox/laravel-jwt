<?php

namespace Sprocketbox\JWT\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;

/**
 * Class KeyGenerateCommand
 *
 * @package Sprocketbox\JWT\Commands
 */
class KeyGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:generate {guard}
                    {--length : The length of the key, defaults to 32}
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the JWT key for the given guard';

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $key    = $this->generateRandomKey();
        $envKey = 'JWT_KEY_' . strtoupper($this->argument('guard'));

        if ($this->option('show')) {
            $this->line('<comment>' . $key . '</comment>');

            return;
        }

        if (! $this->setEnvVariable($envKey, $key)) {
            return;
        }

        $this->info('JWT key set successfully for \'' . $this->argument('guard') . '\'.');
    }

    /**
     * Generate a random key for the JWT signing.
     *
     * @return string
     * @throws \Exception
     */
    protected function generateRandomKey(): string
    {
        return Str::random($this->option('length') ?: 32);
    }

    /**
     * Set the value in the environment file.
     *
     * @param string $envKey
     * @param string $key
     *
     * @return bool
     */
    private function setEnvVariable(string $envKey, string $key): bool
    {
        $currentKey = env($envKey);

        if (($currentKey !== '' || $currentKey !== null) && ! $this->confirmToProceed()) {
            return false;
        }

        if ($currentKey !== null) {
            $this->replaceKeyInEnv($envKey, $key, $currentKey);
        } else {
            $this->addKeyToEnv($envKey, $key);
        }

        return true;
    }

    /**
     * Replace the old JWT key in the env file.
     *
     * @param string $envKey
     * @param string $key
     * @param        $currentKey
     */
    private function replaceKeyInEnv(string $envKey, string $key, $currentKey): void
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            '/^' . preg_quote($envKey . '=' . $currentKey, '/') . '/m',
            $envKey . '=' . $key,
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Add the JWT key to the env file.
     *
     * @param string $envKey
     * @param string $key
     */
    private function addKeyToEnv(string $envKey, string $key): void
    {
        file_put_contents(
            $this->laravel->environmentFilePath(),
            PHP_EOL . $envKey . '=' . $key . PHP_EOL,
            FILE_APPEND
        );
    }
}