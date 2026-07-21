# IWK NBS — Completion Demonstration Prototype

Deployable Laravel 11 demo of IWK's New Billing System in two states —
**Version 1 As-Is (incomplete)** vs **Completed (proposed)** — with working
business logic (receipt allocation, billing runs, instalment arrangements,
enquiry workflow, freezable forecasting) on a deterministic fictitious dataset.
What is missing or partial in v1 is enforced **server-side** from IWK's own
Appendix 11 grading (460 available · 44 enhancement · 90 gap).

- Deployment (local, server, Docker/Coolify): see **DEPLOY.md**
- Feature gate map (every gate anchored to named Appendix 11 processes): `config/nbs.php`
- Business logic: `app/Services/`
- Healthcheck endpoint: `/up`

> Illustrative prototype reconstructed from RFP v1.9 and Appendix 11 before
> source-code access (4–5 Aug 2026, RFP §9.3). Not IWK production software.
> All data fictitious. Deploy behind your own access control.
