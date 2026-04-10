<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Nested groups

This document describes the nested-group feature added for
[nextcloud/server#36150](https://github.com/nextcloud/server/issues/36150).
It is intended for maintainers and admins evaluating the behavioural,
security, and performance implications of enabling nesting on a running
instance.

## Concept

A group can be made a direct subgroup of another group via a new
`group_group(parent_gid, child_gid)` table maintained by the database
group backend (`OC\Group\Database`). Membership is composed transitively
by `OC\Group\Manager`:

- A user is an **effective** member of every ancestor of every group
  they are directly in.
- A direct user membership (`group_user`) is never altered by nesting;
  only the *effective* view changes.

Two separate APIs exist on `IGroupManager`:

| Method                              | Semantics                                   |
|-------------------------------------|---------------------------------------------|
| `getUserGroupIds($user)`            | direct memberships only (unchanged)         |
| `getUserEffectiveGroupIds($user)`   | direct + all ancestors via nesting edges    |

## Cycle prevention

Cycles are rejected at insert time in `OC\Group\Database::addGroupToGroup()`
via a BFS reachability check, run inside a serialized transaction so that
concurrent inserts cannot race a cycle into existence. Self-edges and
duplicate edges are rejected the same way.

## Event synthesis

When a subgroup edge is added or removed, `OC\Group\Manager::addSubGroup()` /
`removeSubGroup()` dispatches:

1. A single `SubGroupAddedEvent` / `SubGroupRemovedEvent`.
2. Per-user `UserAddedEvent` / `UserRemovedEvent` for every user who
   gains or loses *effective* membership of the parent.

The per-user events exist so that existing listeners - notably the
server-side encryption app, which re-keys files on user add/remove -
stay consistent when nesting changes shift the effective recipient set
of a group share. See the **Encryption** caveat below.

Per-user synthesis is bounded by a static cap
(`Manager::MAX_SYNTHESIZED_USER_EVENTS`, currently `500`). Beyond that,
a warning is logged and the per-user events are skipped; admins must
then manually trigger any dependent rebuilds. The cap is there to bound
worst-case request duration when nesting a group that already contains
thousands of users.

## Caveats

### Server-side encryption

If `apps/encryption` is enabled, nested-group mutations may leave the
key distribution out of sync for effective members gained or lost past
the `MAX_SYNTHESIZED_USER_EVENTS` cap. This is not addressed
automatically; the admin must run a manual re-key pass after bulk
nesting changes on encrypted instances. A prominent warning in
`nextcloud.log` indicates when this is required.

### Deleting a middle group

Deleting a group that has both a parent and a child transparently
removes the two edges touching it; the remaining groups are
disconnected. The admin is not warned and no splice is performed
(`A -> B -> C` with `B` deleted does not become `A -> C`). Review the
hierarchy before deleting intermediate groups.

### LDAP and other non-database backends

Nesting is an opt-in capability declared by `INestedGroupBackend` and
implemented only by `OC\Group\Database`. LDAP, SAML and other external
backends are unaware of nesting: their groups can appear on either
side of a `group_group` edge, but the nesting is maintained purely in
the Nextcloud database. If the external backend already exposes its
own nested-group concept (e.g. LDAP `memberOf`), the two compose -
effective membership is the union.

`OC\Group\Manager::collectEffectiveUserIds()` enumerates users in
descendant groups via `Group::searchUsers('')`, which on LDAP triggers
a paginated backend query. Nesting a large LDAP group may therefore
block the edge-mutation request for several seconds. Consider running
nesting changes via `occ` during off-hours for large LDAP groups.

## Public API additions

- `OCP\IGroupManager::getUserEffectiveGroupIds(IUser)`
- `OCP\IGroupManager::addSubGroup(IGroup $parent, IGroup $child)`
- `OCP\IGroupManager::removeSubGroup(IGroup $parent, IGroup $child)`
- `OCP\IGroupManager::getDirectChildGroupIds(string)`
- `OCP\IGroupManager::getDirectParentGroupIds(string)`
- `OCP\IGroupManager::getGroupEffectiveDescendantIds(IGroup)`
- `OCP\IGroupManager::getGroupEffectiveAncestorIds(IGroup)`
- `OCP\Group\Events\SubGroupAddedEvent`
- `OCP\Group\Events\SubGroupRemovedEvent`
- `OCP\Group\Exception\CycleDetectedException`
- `OCP\Group\Exception\NestedGroupsNotSupportedException`

## Out of scope

- Closure table / recursive CTE optimization. BFS is O(depth * fan-out)
  per request with per-request memoization; acceptable for shallow
  hierarchies, suboptimal for deep ones. Add a closure table if
  profiling shows it.
- Audit log integration. `SubGroupAddedEvent` / `SubGroupRemovedEvent`
  are dispatched so an app can listen, but nothing ships.
- UI splice-on-delete for intermediate groups.
- Circles app interop.
