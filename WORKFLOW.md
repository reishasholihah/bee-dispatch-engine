# How this was built

BeeDispatch-Engine was built over a weekend with AI assistants. This file is an honest account of how: what I asked for, what the AI produced, what I checked, and what went wrong.

## Tools

- **Claude (chat):** wrote most of the code, one step at a time. It had no access to my repository and couldn't run my code, so I ran every test myself and pasted failures back.
- **Gemini:** used to draft and refine the step-by-step prompts I gave Claude.
- **GitHub Copilot (VS Code):** worked directly in my workspace to clean up the repository, removing files the project didn't need.

## The loop

Each step followed the same cycle:

1. I wrote a spec for one step: the files to create, the behaviour, and the tests that must exist.
2. The assistant generated the files.
3. I dropped them into the project and ran `composer test`.
4. I pasted any failures back, and we fixed them before moving on.

The steps were: schema and models, the dispatch service, the API layer, seed data, then these docs.

## What the AI got wrong or I had to catch

**Names in my spec didn't match the code that already passed.** My Step 2 spec used `current_lat`, `DeliveryAssignment` and `DISPATCHED`, but Step 1 had already shipped `latitude`, `Assignment` and `Assigned`. We kept the tested schema and mapped my spec onto it instead of renaming working code.

**A square box is not a circle.** The bounding-box prefilter alone lets drivers in the box corners through, even though they are farther than the search radius (about 13.8 km on a 10 km box). The service re-checks the exact Haversine distance after the prefilter, and there is a test for it.

**Laravel returns `201` for a freshly created model.** `JsonResource` does this automatically, which would have contradicted the endpoint's `200` contract, so the controller sets the status explicitly.

**The API routes weren't loaded, and the tests hid it.** A fresh Laravel install doesn't register `routes/api.php`. My first run had 4 failing feature tests, and the pattern gave it away: the tests expecting `200` and `409` failed, while two `404` tests passed only because a missing route also returns `404`. The fix was registering the API routing in `bootstrap/app.php`. The lesson: a test that can pass for the wrong reason is a weak test, so I added a test asserting the orders.dispatch route exists, which fails with a clear message if routing is ever missing.

**The stock agent file was wrong for this repo.** The `CLAUDE.md` Laravel generated was a bootstrap that told agents to run installers. I replaced it with a short `AGENTS.md` that states this project's own conventions.

## What was and wasn't verified

The AI could only syntax-check the code it wrote. Behaviour was verified by the test suite I ran locally. Where the AI could do pure maths without Laravel (the Haversine distances and the bounding-box corner case), it checked the numbers in a plain PHP script before handing the tests over.

## Conventions the agents followed

See [AGENTS.md](AGENTS.md): PSR-12 with Pint, strict types, backed enums for statuses, thin controllers with logic in services, API Resources for JSON, and a feature test for every endpoint plus the edge cases.
