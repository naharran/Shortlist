<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Skill;
use App\Services\HeuristicService;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        if (Skill::count() === 0) {
            $this->call(SkillSeeder::class);
        }

        $skillIds = Skill::pluck('id', 'name');
        $heuristic = app(HeuristicService::class);

        $candidates = [
            ['name' => 'Alice Chen', 'email' => 'alice.chen@example.com', 'phone_number' => '0501111001', 'position' => 'Senior React Developer', 'overall_experience' => 8, 'top_skills' => ['React', 'TypeScript', 'CSS / Styling'], 'moderate_skills' => ['Testing'], 'status' => 'pending', 'cover_letter' => 'I build scalable React dashboards with TypeScript, Redux state management, and component libraries. Strong focus on accessibility and responsive design across enterprise SPAs.'],
            ['name' => 'Bob Martinez', 'email' => 'bob.martinez@example.com', 'phone_number' => '0501111002', 'position' => 'Laravel Backend Engineer', 'overall_experience' => 5, 'top_skills' => ['Laravel', 'REST APIs', 'PostgreSQL'], 'moderate_skills' => ['Docker'], 'status' => 'pending', 'cover_letter' => 'Five years developing Laravel REST APIs with Eloquent ORM, migrations, and queue workers. Experienced with PostgreSQL schema design and Dockerized deployment pipelines.'],
            ['name' => 'Carla Nguyen', 'email' => 'carla.nguyen@example.com', 'phone_number' => '0501111003', 'position' => 'Vue.js Frontend Developer', 'overall_experience' => 3, 'top_skills' => ['Vue', 'TypeScript', 'CSS / Styling'], 'moderate_skills' => ['REST APIs'], 'status' => 'shortlisted', 'cover_letter' => 'Vue and Nuxt specialist building reactive single-file components with Pinia. I integrate REST APIs and deliver polished UI components for SaaS products.'],
            ['name' => 'David Kim', 'email' => 'david.kim@example.com', 'phone_number' => '0501111004', 'position' => 'Python Data Engineer', 'overall_experience' => 6, 'top_skills' => ['Python', 'PostgreSQL', 'REST APIs'], 'moderate_skills' => ['Docker'], 'status' => 'pending', 'cover_letter' => 'Python backend engineer with FastAPI and Django experience. I design data pipelines, machine learning integrations, and high-throughput REST APIs backed by PostgreSQL.'],
            ['name' => 'Elena Rossi', 'email' => 'elena.rossi@example.com', 'phone_number' => '0501111005', 'position' => 'DevOps Engineer', 'overall_experience' => 7, 'top_skills' => ['Docker', 'CI/CD', 'Cloud (AWS / GCP / Azure)'], 'moderate_skills' => ['Go / Golang'], 'status' => 'pending', 'cover_letter' => 'DevOps engineer automating CI/CD with GitHub Actions, Kubernetes orchestration, and AWS infrastructure. Passionate about zero-downtime deployments and observability.'],
            ['name' => 'Frank Okafor', 'email' => 'frank.okafor@example.com', 'phone_number' => '0501111006', 'position' => 'Junior Frontend Developer', 'overall_experience' => 1, 'top_skills' => ['React', 'CSS / Styling'], 'moderate_skills' => ['Git'], 'status' => 'rejected', 'cover_letter' => 'Recent bootcamp graduate eager to contribute to React projects. Comfortable with JSX, hooks, and Tailwind utility classes.'],
            ['name' => 'Grace Patel', 'email' => 'grace.patel@example.com', 'phone_number' => '0501111007', 'position' => 'Full-stack Developer', 'overall_experience' => 4, 'top_skills' => ['React', 'Node.js', 'MongoDB'], 'moderate_skills' => ['GraphQL'], 'status' => 'pending', 'cover_letter' => 'Full-stack developer shipping MERN applications with Express REST APIs and MongoDB document stores. Exploring GraphQL with Apollo for flexible data fetching.'],
            ['name' => 'Henry Walsh', 'email' => 'henry.walsh@example.com', 'phone_number' => '0501111008', 'position' => 'Angular Architect', 'overall_experience' => 10, 'top_skills' => ['Angular', 'TypeScript', 'REST APIs'], 'moderate_skills' => ['Testing'], 'status' => 'shortlisted', 'cover_letter' => 'Angular architect leading enterprise SPAs with RxJS, NgRx, and dependency injection patterns. Deep TypeScript expertise and mentorship of frontend teams.'],
            ['name' => 'Iris Johansson', 'email' => 'iris.johansson@example.com', 'phone_number' => '0501111009', 'position' => 'Go Microservices Engineer', 'overall_experience' => 5, 'top_skills' => ['Go / Golang', 'Microservices', 'PostgreSQL'], 'moderate_skills' => ['Docker'], 'status' => 'pending', 'cover_letter' => 'Golang engineer building low-latency microservices with goroutines, channels, and event-driven architecture. PostgreSQL for transactional workloads.'],
            ['name' => 'James Liu', 'email' => 'james.liu@example.com', 'phone_number' => '0501111010', 'position' => 'Mobile Developer', 'overall_experience' => 3, 'top_skills' => ['React Native', 'TypeScript'], 'moderate_skills' => ['REST APIs'], 'status' => 'pending', 'cover_letter' => 'React Native developer shipping cross-platform iOS and Android apps with Expo. Integrates REST APIs and optimizes mobile performance.'],
            ['name' => 'Karen Dubois', 'email' => 'karen.dubois@example.com', 'phone_number' => '0501111011', 'position' => 'QA Automation Engineer', 'overall_experience' => 4, 'top_skills' => ['Testing', 'CI/CD'], 'moderate_skills' => ['Python'], 'status' => 'pending', 'cover_letter' => 'QA engineer writing Playwright end-to-end tests and PHPUnit integration suites. Advocates test-driven development inside CI/CD pipelines.'],
            ['name' => 'Leo Fernandez', 'email' => 'leo.fernandez@example.com', 'phone_number' => '0501111012', 'position' => 'Backend Developer', 'overall_experience' => 2, 'top_skills' => ['Node.js', 'MySQL', 'REST APIs'], 'moderate_skills' => ['Redis'], 'status' => 'rejected', 'cover_letter' => 'Node.js backend developer building Express APIs with MySQL relational schemas and Redis caching for session management.'],
            ['name' => 'Maya Singh', 'email' => 'maya.singh@example.com', 'phone_number' => '0501111013', 'position' => 'Platform Engineer', 'overall_experience' => 9, 'top_skills' => ['Docker', 'Microservices', 'Cloud (AWS / GCP / Azure)'], 'moderate_skills' => ['Go / Golang'], 'status' => 'pending', 'cover_letter' => 'Platform engineer designing distributed systems on AWS with service mesh, load balancing, and auto-scaling. Strong software architecture background.'],
            ['name' => 'Noah Becker', 'email' => 'noah.becker@example.com', 'phone_number' => '0501111014', 'position' => 'GraphQL API Developer', 'overall_experience' => 4, 'top_skills' => ['GraphQL', 'Node.js', 'PostgreSQL'], 'moderate_skills' => ['TypeScript'], 'status' => 'pending', 'cover_letter' => 'GraphQL API developer defining schemas, resolvers, and subscriptions with Apollo Server. PostgreSQL backing store with optimized queries.'],
            ['name' => 'Olivia Grant', 'email' => 'olivia.grant@example.com', 'phone_number' => '0501111015', 'position' => 'Technical Lead', 'overall_experience' => 12, 'top_skills' => ['Full-stack Development', 'System Design', 'Technical Leadership'], 'moderate_skills' => ['React', 'Laravel'], 'status' => 'shortlisted', 'cover_letter' => 'Technical lead guiding full-stack teams through system design, code reviews, and agile delivery. Hands-on with React frontends and Laravel backends.'],
            ['name' => 'Paul Okonkwo', 'email' => 'paul.okonkwo@example.com', 'phone_number' => '0501111016', 'position' => 'Entry-level Developer', 'overall_experience' => 0, 'top_skills' => ['React', 'Git'], 'moderate_skills' => [], 'status' => 'pending', 'cover_letter' => 'Self-taught developer with personal React projects on GitHub. Learning REST APIs and eager for first production role.'],
            ['name' => 'Quinn Harper', 'email' => 'quinn.harper@example.com', 'phone_number' => '0501111017', 'position' => 'Site Reliability Engineer', 'overall_experience' => 6, 'top_skills' => ['Docker', 'CI/CD', 'Redis'], 'moderate_skills' => ['Python'], 'status' => 'pending', 'cover_letter' => 'SRE focused on reliability, caching with Redis, and automated incident response. Builds observability into Dockerized microservice deployments.'],
            ['name' => 'Rita Alvarez', 'email' => 'rita.alvarez@example.com', 'phone_number' => '0501111018', 'position' => 'Database Administrator', 'overall_experience' => 11, 'top_skills' => ['PostgreSQL', 'MySQL', 'Database Design'], 'moderate_skills' => ['Python'], 'status' => 'pending', 'cover_letter' => 'Database administrator specializing in PostgreSQL performance tuning, index optimization, and complex SQL query design for high-traffic applications.'],
            ['name' => 'Sam Taylor', 'email' => 'sam.taylor@example.com', 'phone_number' => '0501111019', 'position' => 'Product Engineer', 'overall_experience' => 5, 'top_skills' => ['Vue', 'Laravel', 'REST APIs'], 'moderate_skills' => ['Testing'], 'status' => 'rejected', 'cover_letter' => 'Product engineer pairing Vue frontends with Laravel APIs. Ships features end-to-end with Vitest unit tests and collaborative agile workflows.'],
            ['name' => 'Tina Morales', 'email' => 'tina.morales@example.com', 'phone_number' => '0501111020', 'position' => 'Cloud Native Developer', 'overall_experience' => 7, 'top_skills' => ['Cloud (AWS / GCP / Azure)', 'Docker', 'Node.js'], 'moderate_skills' => ['Microservices'], 'status' => 'pending', 'cover_letter' => 'Cloud native developer deploying serverless Lambda functions and containerized Node.js services on AWS with infrastructure as code practices.'],
        ];

        foreach ($candidates as $data) {
            $topIds = array_map(fn (string $name) => $skillIds[$name], $data['top_skills']);
            $moderateIds = array_map(fn (string $name) => $skillIds[$name], $data['moderate_skills']);

            $forAnalysis = new Application([
                'name'               => $data['name'],
                'email'              => $data['email'],
                'phone_number'       => $data['phone_number'],
                'position'           => $data['position'],
                'overall_experience' => $data['overall_experience'],
                'top_skills'         => $data['top_skills'],
                'moderate_skills'    => $data['moderate_skills'],
                'cover_letter'       => $data['cover_letter'],
            ]);

            $analysis = $heuristic->analyze($forAnalysis);

            Application::create([
                'name'               => $data['name'],
                'email'              => $data['email'],
                'phone_number'       => $data['phone_number'],
                'position'           => $data['position'],
                'overall_experience' => $data['overall_experience'],
                'top_skills'         => $topIds,
                'moderate_skills'    => $moderateIds,
                'cover_letter'       => $data['cover_letter'],
                'status'             => $data['status'],
                'risk_score'         => $analysis['risk_score'],
                'heuristic_flags'    => $analysis['heuristic_flags'],
                'reviewed_at'        => $data['status'] !== 'pending' ? now() : null,
            ]);
        }
    }
}
