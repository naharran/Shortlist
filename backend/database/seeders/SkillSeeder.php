<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // ─── Frontend Frameworks ──────────────────────────────────────────
            [
                'name' => 'React',
                'type' => 'specific',
                'related_keywords' => [
                    'react', 'jsx', 'next.js', 'nextjs',
                    // ecosystem core concepts
                    'state management', 'redux', 'component-based', 'reusable components',
                    'single page application', 'spa', 'virtual dom', 'client-side rendering',
                    // what they built
                    'ui components', 'dashboard', 'frontend architecture', 'component library',
                    'data fetching', 'context', 'react query',
                ],
            ],
            [
                'name' => 'Vue',
                'type' => 'specific',
                'related_keywords' => [
                    'vue', 'nuxt', 'nuxtjs', 'vue router', 'pinia', 'vuex',
                    // ecosystem core concepts
                    'composition api', 'options api', 'reactive', 'reactivity',
                    'single file component', 'sfc',
                    // what they built
                    'spa', 'single page application', 'ui components', 'frontend architecture',
                ],
            ],
            [
                'name' => 'Angular',
                'type' => 'specific',
                'related_keywords' => [
                    'angular', 'rxjs', 'ngrx', 'typescript framework',
                    // ecosystem core concepts
                    'dependency injection', 'observables', 'decorators', 'modules',
                    'two-way binding', 'data binding',
                    // what they built
                    'enterprise frontend', 'spa', 'single page application', 'ui components',
                ],
            ],
            [
                'name' => 'TypeScript',
                'type' => 'specific',
                'related_keywords' => [
                    'typescript', 'ts',
                    // core concepts
                    'type safety', 'static typing', 'strongly typed', 'typed javascript',
                    'interfaces', 'generics', 'type annotations', 'compile-time',
                    // what they emphasize
                    'scalable codebase', 'maintainable code', 'type system',
                ],
            ],
            [
                'name' => 'CSS / Styling',
                'type' => 'specific',
                'related_keywords' => [
                    'css', 'tailwind', 'sass', 'scss', 'styled-components', 'css modules',
                    // core concepts
                    'responsive design', 'mobile-first', 'flexbox', 'grid layout',
                    'design system', 'utility classes', 'theming', 'dark mode',
                    // what they built
                    'pixel-perfect', 'animations', 'transitions', 'ui design', 'layout',
                    'cross-browser', 'accessibility', 'a11y',
                ],
            ],

            // ─── Backend Frameworks ───────────────────────────────────────────
            [
                'name' => 'Node.js',
                'type' => 'specific',
                'related_keywords' => [
                    'node', 'nodejs', 'express', 'fastify', 'nestjs',
                    // core concepts
                    'server-side javascript', 'event-driven', 'non-blocking', 'asynchronous',
                    'async', 'rest api', 'microservices', 'api server',
                    // what they built
                    'backend service', 'web server', 'api gateway', 'middleware',
                    'real-time', 'websocket',
                ],
            ],
            [
                'name' => 'Laravel',
                'type' => 'specific',
                'related_keywords' => [
                    'laravel', 'php',
                    // core concepts
                    'mvc', 'eloquent', 'orm', 'migrations', 'artisan', 'blade',
                    'sanctum', 'passport', 'queues', 'jobs',
                    // what they built
                    'rest api', 'web application', 'backend', 'api development',
                    'authentication', 'authorization', 'role-based',
                ],
            ],
            [
                'name' => 'Python',
                'type' => 'specific',
                'related_keywords' => [
                    'python', 'django', 'flask', 'fastapi',
                    // core concepts
                    'scripting', 'automation', 'data processing', 'orm', 'async',
                    // what they built
                    'backend', 'rest api', 'web application', 'data pipeline',
                    'machine learning', 'ml model', 'data science', 'api server',
                ],
            ],
            [
                'name' => 'Go / Golang',
                'type' => 'specific',
                'related_keywords' => [
                    'go', 'golang',
                    // core concepts
                    'concurrency', 'goroutines', 'channels', 'compiled language', 'high performance',
                    // what they built
                    'microservices', 'cli tool', 'backend service', 'api server', 'low latency',
                ],
            ],

            // ─── API & Architecture ───────────────────────────────────────────
            [
                'name' => 'REST APIs',
                'type' => 'specific',
                'related_keywords' => [
                    'rest', 'restful', 'api design', 'api development',
                    // core concepts
                    'http', 'json', 'endpoints', 'crud', 'http methods', 'status codes',
                    'request', 'response', 'authentication', 'authorization', 'token',
                    // what they built
                    'api integration', 'backend services', 'web api', 'api documentation',
                    'swagger', 'openapi', 'versioning',
                ],
            ],
            [
                'name' => 'GraphQL',
                'type' => 'specific',
                'related_keywords' => [
                    'graphql', 'apollo',
                    // core concepts
                    'typed api', 'schema', 'queries', 'mutations', 'subscriptions',
                    'resolvers', 'data graph',
                    // what they built
                    'flexible api', 'api', 'data fetching',
                ],
            ],
            [
                'name' => 'Microservices',
                'type' => 'specific',
                'related_keywords' => [
                    'microservices', 'service-oriented', 'distributed system',
                    // core concepts
                    'independent services', 'service mesh', 'event-driven architecture',
                    'message queue', 'async communication', 'api gateway',
                    // what they built
                    'scalable architecture', 'decoupled', 'fault tolerant',
                ],
            ],

            // ─── Databases ────────────────────────────────────────────────────
            [
                'name' => 'PostgreSQL',
                'type' => 'specific',
                'related_keywords' => [
                    'postgres', 'postgresql',
                    // core concepts
                    'relational database', 'sql', 'joins', 'indexes', 'transactions',
                    'query optimization', 'schemas', 'data modeling', 'constraints',
                    // what they built
                    'database design', 'complex queries', 'performance tuning',
                ],
            ],
            [
                'name' => 'MySQL',
                'type' => 'specific',
                'related_keywords' => [
                    'mysql', 'mariadb',
                    // core concepts
                    'relational database', 'sql', 'joins', 'indexes', 'stored procedures',
                    'data modeling', 'transactions',
                    // what they built
                    'database design', 'schema design',
                ],
            ],
            [
                'name' => 'MongoDB',
                'type' => 'specific',
                'related_keywords' => [
                    'mongodb', 'mongo', 'mongoose',
                    // core concepts
                    'nosql', 'document database', 'collections', 'aggregation',
                    'unstructured data', 'flexible schema', 'horizontal scaling',
                    // what they built
                    'scalable database', 'document store',
                ],
            ],
            [
                'name' => 'Redis',
                'type' => 'specific',
                'related_keywords' => [
                    'redis',
                    // core concepts
                    'caching', 'cache layer', 'session management', 'pub/sub',
                    'message queue', 'in-memory', 'key-value store',
                    // what they built
                    'performance optimization', 'rate limiting', 'real-time features',
                ],
            ],

            // ─── DevOps & Infrastructure ──────────────────────────────────────
            [
                'name' => 'Docker',
                'type' => 'specific',
                'related_keywords' => [
                    'docker', 'kubernetes', 'k8s',
                    // core concepts
                    'containerization', 'containers', 'container orchestration',
                    'infrastructure as code', 'isolated environment', 'image',
                    // what they built
                    'deployment pipeline', 'scalable infrastructure', 'microservices deployment',
                    'local development environment',
                ],
            ],
            [
                'name' => 'CI/CD',
                'type' => 'specific',
                'related_keywords' => [
                    'ci/cd', 'github actions', 'gitlab ci', 'jenkins', 'circleci',
                    // core concepts
                    'continuous integration', 'continuous delivery', 'continuous deployment',
                    'automated deployment', 'deployment pipeline', 'build pipeline',
                    // what they built
                    'release automation', 'automated testing pipeline', 'zero-downtime deployment',
                    'devops practices',
                ],
            ],
            [
                'name' => 'Cloud (AWS / GCP / Azure)',
                'type' => 'specific',
                'related_keywords' => [
                    'aws', 'gcp', 'azure', 'ec2', 's3', 'lambda', 'rds', 'cloudfront',
                    // core concepts
                    'cloud infrastructure', 'serverless', 'managed services', 'cloud architecture',
                    'auto-scaling', 'load balancing', 'iam', 'cloud services',
                    // what they built
                    'scalable infrastructure', 'cloud deployment', 'distributed system',
                ],
            ],

            // ─── Testing ──────────────────────────────────────────────────────
            [
                'name' => 'Testing',
                'type' => 'specific',
                'related_keywords' => [
                    'jest', 'vitest', 'phpunit', 'cypress', 'playwright', 'mocha',
                    // core concepts
                    'unit testing', 'integration testing', 'end-to-end testing', 'e2e',
                    'test-driven development', 'tdd', 'test coverage', 'regression testing',
                    // what they value
                    'code quality', 'automated tests', 'quality assurance', 'bug prevention',
                    'reliable software',
                ],
            ],

            // ─── Mobile ───────────────────────────────────────────────────────
            [
                'name' => 'React Native',
                'type' => 'specific',
                'related_keywords' => [
                    'react native', 'expo',
                    // core concepts
                    'cross-platform', 'mobile development', 'ios', 'android',
                    'native components', 'mobile app',
                    // what they built
                    'mobile application', 'app store', 'play store',
                ],
            ],

            // ─── Tooling ──────────────────────────────────────────────────────
            [
                'name' => 'Git',
                'type' => 'specific',
                'related_keywords' => [
                    'git', 'github', 'gitlab', 'bitbucket',
                    // core concepts
                    'version control', 'branching strategy', 'pull request', 'code review',
                    'merge', 'rebase', 'git flow',
                    // what they practice
                    'collaborative development', 'team workflow',
                ],
            ],

            // ─── Broad Skills ─────────────────────────────────────────────────
            ['name' => 'Front-end Development',   'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Back-end Development',    'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Full-stack Development',  'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Database Design',         'type' => 'broad', 'related_keywords' => []],
            ['name' => 'System Design',           'type' => 'broad', 'related_keywords' => []],
            ['name' => 'DevOps',                  'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Mobile Development',      'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Agile / Scrum',           'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Software Architecture',   'type' => 'broad', 'related_keywords' => []],
            ['name' => 'Technical Leadership',    'type' => 'broad', 'related_keywords' => []],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
