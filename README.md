# resouces

Setup:

1) Populate .env.example file and save in root directory as .env; see https://github.com/vlucas/phpdotenv for documentation.

2) Instantiate framework, passing in root directory:
	$framework = new \glasteel\FrameworkInit(__DIR__);
	