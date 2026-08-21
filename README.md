# qtype_saylorcode

A Moodle question type for coding exercises. The student writes code; it is
compiled and run against test cases to mark it.

Part of Saylor Code Studio. Requires
[local_saylorcode](https://github.com/saylordotorg/moodle-local_saylorcode),
which provides the exercise library, the runner and the resolver.

## Why this exists rather than qtype_coderunner

The specification originally called for `qtype_coderunner` plus a
`qbank_saylorcode` plugin. CodeRunner is itself a Jobe client, so adopting it
would put two independent runner integrations on one site: two authoring
surfaces, two test-case formats, and two places to configure sandbox limits.

This plugin instead reuses what the suite already has — one runner, one library,
one test-case shape — so an exercise can back a stand-alone activity, a Book
embed and a quiz question without being written three times.

## Grading

Marking runs the student's code, so it depends on a runner being available.

When the runner cannot be reached, is saturated, or reports a platform failure,
the question returns `needsgrading` rather than a mark. A student who wrote
correct code during an outage lands in the teacher's marking queue instead of
receiving a zero indistinguishable from a wrong answer.

A compile error is treated differently: that belongs to the submitted code, so
it is a real zero.

## Known limitations

- **No per-case feedback.** Showing which cases passed would mean either
  re-running the code at render time or storing per-case outcomes where the
  renderer can read them. The second is how a hidden case's name reaches a page
  it should never appear on, so for now the mark is shown and the breakdown is
  not.
- **Plain textarea, not the rich editor.** The CodeMirror editor lives in
  `mod_saylorcode`, and a question type reaching into an activity for its UI
  would be the wrong dependency. The fix is to move that editor down into
  `local_saylorcode` so both share it.
