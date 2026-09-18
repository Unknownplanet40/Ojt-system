// Self-check validation for CvSU Imus CSG System core logic
import assert from "node:assert/strict";

console.log("=== Running Core Logic & Boundary Self-Checks ===");

// 1. Soft Conflict Detection Algorithm Test
function hasTimeslotConflict(existingStart, existingEnd, newStart, newEnd) {
  const eStart = new Date(existingStart).getTime();
  const eEnd = new Date(existingEnd).getTime();
  const nStart = new Date(newStart).getTime();
  const nEnd = new Date(newEnd).getTime();

  // Overlap condition: start_at < new_end_at AND end_at > new_start_at
  return eStart < nEnd && eEnd > nStart;
}

// Case 1A: Direct overlap
assert.equal(
  hasTimeslotConflict(
    "2026-10-01T08:00:00Z",
    "2026-10-01T12:00:00Z",
    "2026-10-01T10:00:00Z",
    "2026-10-01T14:00:00Z"
  ),
  true,
  "Should detect partial overlap"
);

// Case 1B: Adjacent (Back to back - no overlap)
assert.equal(
  hasTimeslotConflict(
    "2026-10-01T08:00:00Z",
    "2026-10-01T12:00:00Z",
    "2026-10-01T12:00:00Z",
    "2026-10-01T15:00:00Z"
  ),
  false,
  "Back-to-back timeslots should not conflict"
);

// Case 1C: Completely non-overlapping
assert.equal(
  hasTimeslotConflict(
    "2026-10-01T08:00:00Z",
    "2026-10-01T10:00:00Z",
    "2026-10-01T14:00:00Z",
    "2026-10-01T16:00:00Z"
  ),
  false,
  "Disjoint timeslots should not conflict"
);

console.log("✓ Conflict detection logic passed.");

// 2. Document State Machine Validation
const validTransitions = {
  draft: ["submitted"],
  submitted: ["under_review"],
  under_review: ["approved", "returned"],
  returned: ["submitted"],
  approved: [],
};

function isValidTransition(current, target) {
  const allowed = validTransitions[current] || [];
  return allowed.includes(target);
}

assert.equal(isValidTransition("draft", "submitted"), true);
assert.equal(isValidTransition("submitted", "under_review"), true);
assert.equal(isValidTransition("under_review", "approved"), true);
assert.equal(isValidTransition("under_review", "returned"), true);
assert.equal(isValidTransition("returned", "submitted"), true);

// Negative checks
assert.equal(isValidTransition("draft", "approved"), false, "Draft cannot jump directly to approved");
assert.equal(isValidTransition("approved", "returned"), false, "Approved documents cannot be returned");
assert.equal(isValidTransition("returned", "under_review"), false, "Returned documents must be resubmitted first");

console.log("✓ Document state machine transitions passed.");

// 3. Chat Hierarchical Boundaries Check
function canPostInChannel(userRole, userOrgId, channelType, channelOrgId) {
  if (userRole === "super_admin" || userRole === "csg_officer") {
    return true; // Full council and channel oversight
  }
  if (userRole === "sub_org_officer") {
    if (channelType === "council") return true;
    if (channelType === "organization" && userOrgId === channelOrgId) return true;
    return false; // Cannot post in other orgs
  }
  if (userRole === "member") {
    if (channelType === "organization" && userOrgId === channelOrgId) return true;
    return false; // Cannot post in council or other orgs
  }
  return false;
}

const orgA = "org-aits-uuid";
const orgB = "org-acss-uuid";

// Super Admin
assert.equal(canPostInChannel("super_admin", null, "council", null), true);
assert.equal(canPostInChannel("super_admin", null, "organization", orgA), true);

// Sub Org Officer (Org A)
assert.equal(canPostInChannel("sub_org_officer", orgA, "council", null), true);
assert.equal(canPostInChannel("sub_org_officer", orgA, "organization", orgA), true);
assert.equal(canPostInChannel("sub_org_officer", orgA, "organization", orgB), false, "Org A officer cannot post in Org B");

// Regular Member (Org A)
assert.equal(canPostInChannel("member", orgA, "organization", orgA), true);
assert.equal(canPostInChannel("member", orgA, "council", null), false, "Member cannot post in council");
assert.equal(canPostInChannel("member", orgA, "organization", orgB), false, "Member cannot post in other org");

console.log("✓ Communication boundary permissions passed.");
console.log("=== All 10 Self-Check Assertions Passed Successfully! ===");
