# akoflow-deployment-control-plane

**akoflow-deployment-control-plane** is the backend REST API of AkoFlow — a multi-cloud infrastructure provisioning and management platform built on top of Terraform. It allows teams to define reusable infrastructure templates, provision environments across multiple cloud providers (AWS, GCP, Azure, On-Premises, HPC), and track the full lifecycle of provisioned resources.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **PostgreSQL** — primary database
- **Laravel Sanctum** — API token authentication
- **Laravel Queues** — async job processing (Terraform runs)
- **Docker socket** — queue workers spawn Terraform containers at runtime

## Features

- **Environment Templates** — versioned, reusable Terraform module bundles per cloud provider
- **Multi-Cloud Provisioning** — AWS, GCP, Azure, On-Premises and HPC in the same deployment
- **Credential Management** — securely store and map provider credentials per deployment
- **Async Terraform Execution** — queue workers spawn sandboxed Docker containers to run `terraform init/apply/destroy`
- **Real-time Logs** — stream Terraform run logs during provisioning
- **Provisioned Resource Tracking** — automatically parse Terraform output and record created resources
- **Provider Health Checks** — continuous background jobs to validate provider connectivity
- **Organizations & Projects** — multi-tenant model with role-based membership

## Architecture Overview

```
┌───────────────────────────────────────────────────────────────────────┐
│              akoflow-deployment-control-plane                         │
│                  Laravel 12 API (port 8080)                           │
│                                                                       │
│  ┌─────────────┐       ┌──────────────┐       ┌─────────────────┐    │
│  │ Controllers │       │   Services   │       │      Jobs       │    │
│  └──────┬──────┘       └──────┬───────┘       └────────┬────────┘    │
│         └────────────────────┬┘────────────────────────┘             │
│                       ┌──────▼──────┐                                │
│                       │ PostgreSQL  │                                 │
│                       └─────────────┘                                │
└──────────────────────────────┬────────────────────────────────────────┘
                               │ Docker socket
                  ┌────────────▼────────────┐
                  │   Terraform Containers  │
                  │  (apply / destroy runs) │
                  └─────────────────────────┘
```

## Getting Started

### Prerequisites

- Docker and Docker Compose

### Running with Docker

```bash
# 1. Clone the repository
git clone <repo-url> akoflow-deployment-control-plane
cd akoflow-deployment-control-plane

# 2. Copy environment file and configure
cp .env.example .env

# 3. Build images
make build

# 4. Start services (app + PostgreSQL)
make up

# 5. Run migrations and seed initial data
make fresh
```

The API will be available at `http://localhost:8080`.

### Useful Commands

| Command | Description |
|---|---|
| `make up` | Start all services |
| `make down` | Stop all services |
| `make migrate` | Run pending migrations |
| `make fresh` | Drop, migrate and seed the database |
| `make bash` | Open a shell inside the app container |
| `make logs` | Stream container logs |

### Environment Variables

Copy `.env.example` to `.env` and adjust the key variables:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=akocloud
DB_USERNAME=akocloud
DB_PASSWORD=akocloud
```

## API Overview

| Group | Endpoints |
|---|---|
| Auth | Register, Login, Logout, Refresh token, Lost password |
| Users | Profile, change password, delete account |
| Organizations | CRUD, member management |
| Projects | CRUD (scoped to organization) |
| Providers | CRUD, health check, provider-type schema catalog |
| Provider Credentials | CRUD |
| Environment Templates | Versioning, activation, Terraform module upload |
| Environments | Create, provision, list |
| Deployments | CRUD with multi-provider credential mappings |
| Terraform Runs | Trigger apply/destroy, stream logs |
| Provisioned Resources | List by deployment |

## Related

- [akoflow-deployment-control-plane-ui](https://github.com/ovvesley/akoflow-deployment-control-plane-ui) — Frontend (Next.js 16)

## License

MIT
