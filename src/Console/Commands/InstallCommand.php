<?php

namespace CubeSystems\Leaf\Console\Commands;

use Cartalyst\Sentinel\Sentinel;
use CubeSystems\Leaf\Providers\LeafServiceProvider;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * Class SeedCommand
 * @package CubeSystems\Leaf\Console\Commands
 */
class InstallCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $name = 'leaf:install';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var Sentinel
     */
    protected $sentinel;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param Sentinel $sentinel
     * @param Filesystem $filesystem
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        Sentinel $sentinel,
        Filesystem $filesystem,
        DatabaseManager $databaseManager
    )
    {
        $this->sentinel = $sentinel;
        $this->filesystem = $filesystem;
        $this->databaseManager = $databaseManager;

        parent::__construct();
    }

    /**
     *
     */
    public function fire()
    {
        try
        {
            $this->databaseManager->connection();
        }
        catch( Exception $e )
        {
            $this->error( 'Unable to connect to the database.' );
            $this->error( 'Please fill valid database credentials into .env and run this command again.' );

            return;
        }

        $this->publishConfig();
        $this->addWebpackTask();
        $this->runMigrations();
        $this->createAdminUser();
        $this->npmDependencies();

        $this->info( 'Installation completed!' );
    }

    /**
     *
     */
    protected function publishConfig()
    {
        $this->info( 'Publishing configuration file' );
        $this->call( 'vendor:publish', [
            '--provider' => LeafServiceProvider::class,
            '--tag' => 'config',
        ] );
    }

    /**
     *
     */
    protected function addWebpackTask()
    {
        $webpackConfig = 'webpack.mix.js';
        $leafRequire = "require('./vendor/cubesystems/leaf/webpack.mix')(mix);";

        try
        {
            $contents = $this->filesystem->get( $webpackConfig );

            if( strpos( $contents, $leafRequire ) === false )
            {
                $this->filesystem->append( $webpackConfig, PHP_EOL . $leafRequire );
            }
        }
        catch( FileNotFoundException $e )
        {
            $this->error( 'Webpack config not found' );
        }
    }

    /**
     *
     */
    protected function runMigrations()
    {
        $this->info( 'Running migrations' );
        $this->call( 'migrate' );
    }

    /**
     *
     */
    protected function createAdminUser()
    {
        $users = $this->sentinel->getUserRepository();
        $activations = $this->sentinel->getActivationRepository();
        $roles = $this->sentinel->getRoleRepository();

        if( $users->all()->count() > 0 )
        {
            $this->info( 'Admin user already exists' );

            return;
        }

        $this->info( 'Let\'s create admin user' );

        $user = null;

        while( $user === null )
        {
            $email = $this->ask( 'Admin email' );
            $password = $this->secret( 'What is the password?' );

            try
            {
                $user = $users->create( [
                    'email' => $email,
                    'password' => $password
                ] );

                $activation = $activations->create( $user );
                $activations->complete( $user, $activation->getCode() );

                break;
            }
            catch( InvalidArgumentException $exception )
            {
                $this->error( $exception->getMessage() );
            }
        }

        $administratorRole = $roles->create( [
            'name' => 'Administrator',
            'slug' => 'administrator',
            'permissions' => [
                'users.create' => true,
                'users.update' => true,
                'users.view' => true,
                'users.destroy' => true,
                'roles.create' => true,
                'roles.update' => true,
                'roles.view' => true,
                'roles.delete' => true
            ]
        ] );

        $administratorRole->users()->attach( $user );
    }

    /**
     *
     */
    protected function npmDependencies()
    {
        if( !`which npm` )
        {
            $this->comment( 'NPM not found. To complete the installation, run "npm install" and "npm run dev" manually.' );

            return;
        }

        $this->info( 'Installing NPM packages' );
        shell_exec( 'npm install --silent' );

        $this->info( 'Compiling assets' );
        shell_exec( 'npm run dev' );
    }
}