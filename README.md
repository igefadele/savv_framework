# Savo Framework

Savo Framework is a lightweight PHP framework package for building brand websites, marketing websites, studio sites, landing pages, and other public-facing web experiences without the weight of a large full-stack framework.

It is built around plain modern PHP, direct logic, small utilities, and a familiar developer experience. The goal is to give developers the convenience and structure they expect from modern frameworks while keeping the codebase easy to understand, easy to trace, and easy to extend.

Savo Framework is the core package.

If you want to start a new Savo application quickly, use **Savo Starter**, which is the starter skeleton project that already depends on this package and provides the app structure, routes, views, configs, and public entry setup.

## Package Model

Savo is split into two parts:

### 1. Savo Framework

This repository contains the installable framework package.

It provides the core runtime and utilities such as:

- application bootstrapping support
- routing
- request handling
- response helpers
- config loading
- validation
- logging
- helper functions
- other core framework behavior

The framework is installed as a Composer dependency and lives inside `vendor/` in a real application.

### 2. Savo Starter

This is the starter project developers download when they want to create a new Savo app.

It contains the application-level structure, typically things like:

- `app/`
- `configs/`
- `routes/`
- `views/`
- `public/`
- environment setup
- starter assets and example pages

The starter project already includes Savo Framework as a dependency, so developers can begin building immediately.

## Why Savo

- Lightweight core with minimal overhead
- Built for real websites, not bloated application scaffolding
- Plain PHP code with familiar framework-style syntax
- Easy to understand for both experienced developers and newcomers
- File-based page routing support for quick website development
- Custom routing support for APIs and advanced behavior
- Clean helper-driven API for common tasks
- Suitable for starter kits, agencies, studios, and brochure or marketing sites

## Best Use Cases

Savo Framework is especially suitable for:

- brand websites
- marketing sites
- landing pages
- studio or agency websites
- corporate websites
- brochure websites
- websites with a few dynamic endpoints
- public-facing web apps that do not need a heavy framework

If you need a deeply layered enterprise framework with a large built-in ecosystem, Savo may not be the right tool. Savo is optimized for clarity, speed, and low complexity.

## Installation

### Install via Packagist

Install Savo Framework with:

```bash
composer require savadub/savo
```

### Install via GitHub Repository URL

Developers can also install the framework directly from its GitHub repository by adding a VCS repository entry to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/igefadele/savo_framework"
    }
  ],
  "require": {
    "savadub/savo": "dev-main"
  }
}
```

Then run:

```bash
composer update
```

## Recommended Way to Start a New Project

The recommended way to begin a new Savo project is to use **Savo Starter**, not this package repository directly.

Why:

- the starter already has the correct folder structure
- the starter already depends on Savo Framework
- the starter includes the app-level files developers are expected to edit
- developers can begin building pages and features immediately

Suggested flow:

1. Download or clone `Savo Starter` from `https://github.com/igefadele/savo_starter`
2. Run `composer install`
3. Configure environment values
4. Point the server document root to `public/`
5. Start building inside the starter app structure

## Framework Responsibilities

Savo Framework is responsible for the reusable core logic that powers Savo applications.

That includes:

- core classes under `src/`
- framework helpers
- routing engine
- request and response utilities
- validation utilities
- config access utilities
- logging utilities
- application provider and bootstrapping support

This separation keeps application code out of the framework package and makes the framework easier to maintain, version, and reuse.

## Starter Project Responsibilities

Savo Starter is where developers build their real applications.

That project should hold the project-specific code such as:

- controllers
- middleware
- route files
- configs
- views
- public assets
- layouts and partials
- application constants
- custom services

In other words:

- **Savo Framework** provides the engine
- **Savo Starter** provides the starting app shell

## Typical Architecture

In a real Savo project, the structure now looks more like this:

```text
my-savo-app/
app/
configs/
public/
routes/
views/
vendor/
  savadub/
    savo/
```


## Developer Experience

Savo aims to feel familiar without becoming heavy.

Developers can expect:

- a simple and readable coding model
- direct PHP logic
- minimal abstraction
- framework-like helper syntax
- fast onboarding
- less boilerplate for page-driven websites

The framework is intentionally designed so that someone coming from Laravel-like conventions can feel comfortable, while someone with no prior framework experience can still understand what is going on.


## Philosophy

Savo is for developers who want:

- less boilerplate
- less abstraction
- less framework bloat
- more readable PHP
- more direct control
- a framework focused on modern websites

Savo does not try to do everything.

It tries to do the important website-building things clearly, cleanly, and without getting in your way.

## In Short

Use this repository when you want the **Savo Framework package** itself.

Use **Savo Starter** when you want to begin building a real Savo application.


