# Contributing

## Development

Use the commands documented by the repository's `package.json` and `README.md`.

Typical development flow:

```bash
npm install
npm run dev
```

Before opening a pull request, run the project's configured validation commands.

## Validation

Run the scripts that actually exist in `package.json`, typically:

```bash
npm run lint
npm run typecheck
npm run test
npm run build
```

Do not claim a command passed unless you ran it.

## Database

Use the repository's database migration workflow.

Never modify a production database schema manually as part of normal development.

Schema and RLS changes must be reviewed as code.

## Branches

Use descriptive branch names:

```text
feat/<short-name>
fix/<short-name>
refactor/<short-name>
docs/<short-name>
test/<short-name>
```

## Commits

Use Conventional Commits:

```text
feat:
fix:
test:
docs:
refactor:
chore:
```

Keep commits focused.

## Pull Requests

PRs should:

- Explain the change.
- Link the related issue.
- Describe security/authorization implications.
- Include testing performed.
- Mention known limitations.
- Avoid unrelated changes.

## Security

Never commit:

- API keys
- Passwords
- Tokens
- Service-role credentials
- Private certificates
- `.env` files containing secrets

Report security problems privately rather than publishing sensitive details in an issue.
