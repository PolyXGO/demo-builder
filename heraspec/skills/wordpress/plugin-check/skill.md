# Skill: WordPress Plugin Check (PCP)

This skill automates the process of running the WordPress Plugin Check tool and fixing the reported issues.

## Purpose

To identify and resolve issues flagged by the WordPress Plugin Check (PCP) tool to ensure the plugin meets WordPress.org standards.

## Prerequisites

1.  **Install Plugin Check (PCP)**:
    - You MUST have the [Plugin Check (PCP)](https://wordpress.org/plugins/plugin-check/) plugin installed and active on your WordPress site.
    - CLI command relies on this plugin to generate reports.

2.  **Configure wp-config.php**:
    - Ensure your `wp-config.php` uses `127.0.0.1` instead of `localhost` for database host if you encounter connection issues with CLI.
    - Example: `define( 'DB_HOST', '127.0.0.1' );`

## Required Variables

- `{{plugin_folder_name}}`: The actual folder name of the target plugin (e.g., `poly-locations-manager` or `my-awesome-plugin`).
  > **Note**: `polyutilities` in examples is just a placeholder. ALWAYS replace it with the actual plugin slug you are working on.

## Agent Usage & Examples

### Triggering the Skill

Users may ask to "check plugin [name]" or "run plugin check".

**Example Prompt (Reference Only):**
> "Use skill plugin-check to check, then resolve *all* issues mentioned for plugin `[TARGET_PLUGIN_NAME]`. Ensure careful handling and verify that related features still function correctly. If uncertain about any fix, add a TODO note in the code instead of applying a risky change."

**CRITICAL INSTRUCTION**:
- Replace `[TARGET_PLUGIN_NAME]` with the actual plugin folder name provided by the user.
- IF the user does not specify a name, infer it from the current working directory or the `wp-content/plugins/` structure.
- **DO NOT** default to `polyutilities` unless the user specifically asked for it.

### Process Flow

#### Step 1: Run Plugin Check

1. **Identify Plugin Folder**: Ensure you are in the root workspace of the project. The plugin folder is usually the one you are currently working on.
2. **Execute Command**: Run the following command to generate the report. Replace `{{plugin_folder_name}}` with the actual directory name of the plugin.
   ```bash
   mkdir -p wp-content/plugins/{{plugin_folder_name}}/_pcp && wp plugin check {{plugin_folder_name}} --exclude-files=.DS_Store,.sh,.md --exclude-directories=documentations,heraspec,node_modules,tests,dist,_pcp --format=table > wp-content/plugins/{{plugin_folder_name}}/_pcp/pcp.txt
   ```

#### Step 2: Review and Confirm

1. **Read Report**: Read the content of `wp-content/plugins/{{plugin_folder_name}}/_pcp/pcp.txt`.
2. **Notify User**: Inform the user about the number or types of issues found.
3. **Wait for Confirmation**: Do NOT proceed to fix issues automatically without user confirmation unless explicitly authorized to "fix all".

#### Step 3: Fix Issues (Iterative)

1. **Target**: Aim to resolve **all** reported errors and warnings, ensuring no new issues are introduced.
2. **Safety First**: Ensure code changes do not break existing functionality.
   * **Uncertainty**: If a fix is risky or unclear, DO NOT apply it blindly. Instead, add a comment `// TODO: [PCP] Fix this issue manually - [Reason]` and move to the next item.
3. **Iterate**:
   * Apply fixes for a batch of issues.
   * **Re-run Check**: Run the command from Step 1 again to verify fixes.
   * Repeat until the report is clean or only contains intentional TODOs.

## Strict Prohibitions

1.  **DO NOT DELETE Excluded Files**:
    - The command excludes `.sh`, `.md`, `.DS_Store` and directories `heraspec`, `tests`, `docs`.
    - **NEVER** delete or modify these files/directories as part of the fix. They are intentionally excluded because they are development assets, not production garbage.
    - If `wp plugin check` complains about them (it shouldn't if excluded), **IGNORE** those specific errors.

2.  **Scope**: Only modify files within the plugin directory that are relevant to the actual PHP/JS/CSS code being checked.

## Tips

- Common issues include missing escaping (`esc_html`, `esc_attr`), missing nonces, or direct DB access without preparation.
- Always verify that fixes do not break existing functionality.
