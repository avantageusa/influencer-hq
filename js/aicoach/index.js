/**
 * Carrier for the AI Coach module import map (ENGR-7066); no code on purpose.
 *
 * WordPress puts a script module in the import map (which is what gives
 * "@ihq/aicoach/<name>" a URL versioned with the file's modification time) only
 * when another enqueued module lists it as a static dependency. See
 * inc/aicoach-modules.php, where this file is registered with the modules the
 * flow imports as its dependencies. It must not import them itself: a relative
 * import would load them from unversioned URLs as separate module instances.
 */
export {};
