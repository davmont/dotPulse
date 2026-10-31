#!/bin/bash
# Seed data for the audit crawl: companies (with markup in their names), a project
# with two tasks assigned to admin, a forum and an event.
. "$(dirname "$0")/lib.sh"
db -e "INSERT INTO dotp_companies (company_name, company_owner) VALUES ('Alpha & Sons', 1), ('Beta \"Quote\" Ltd', 1), ('<b>Gamma</b>', 1);
SET @c=(SELECT MIN(company_id) FROM dotp_companies);
INSERT INTO dotp_projects (project_name, project_short_name, project_company, project_owner, project_status, project_start_date, project_end_date) VALUES ('Seed Project', 'SEED', @c, 1, 3, NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 30 DAY);
SET @p=LAST_INSERT_ID();
INSERT INTO dotp_tasks (task_name, task_project, task_owner, task_start_date, task_end_date, task_percent_complete, task_dynamic, task_parent, task_milestone, task_duration, task_duration_type) VALUES ('Seed task A', @p, 1, NOW(), NOW() + INTERVAL 3 DAY, 20, 0, 0, 0, 3, 24), ('Seed task B & <i>co</i>', @p, 1, NOW() + INTERVAL 4 DAY, NOW() + INTERVAL 9 DAY, 0, 0, 0, 0, 5, 24);
UPDATE dotp_tasks SET task_parent=task_id WHERE task_project=@p;
INSERT INTO dotp_user_tasks (user_id, task_id, perc_assignment) SELECT 1, task_id, 100 FROM dotp_tasks WHERE task_project=@p;
INSERT INTO dotp_forums (forum_project, forum_status, forum_owner, forum_name, forum_create_date, forum_description, forum_moderated) VALUES (0, 1, 1, 'Seed forum', NOW(), 'd', 0);
INSERT INTO dotp_events (event_title, event_start_date, event_end_date, event_owner, event_type, event_private) VALUES ('Seed event', NOW(), NOW() + INTERVAL 1 HOUR, 1, 1, 0);
UPDATE dotp_config SET config_value = 'true' WHERE config_name = 'api_enabled';"
P=$(db -e "SELECT MAX(project_id) FROM dotp_projects WHERE project_name='Seed Project'")
T=$(db -e "SELECT MIN(task_id) FROM dotp_tasks WHERE task_project=$P")
C=$(db -e "SELECT MIN(company_id) FROM dotp_companies")
# Pages the crawl does not reach through links: every project, task, company and user tab.
{
	for i in $(seq 0 45); do echo "m=projects&a=view&project_id=$P&tab=$i"; done
	for i in $(seq 0 20); do echo "m=tasks&a=view&task_id=$T&tab=$i"; done
	for i in $(seq 0 12); do echo "m=companies&a=view&company_id=$C&tab=$i"; done
	for i in $(seq 0 8); do echo "m=admin&a=viewuser&user_id=1&tab=$i"; done
	echo "m=projects&a=addedit&project_id=$P"
	echo "m=tasks&a=addedit&task_id=$T"
	echo "m=tasks&a=addedit&task_project=$P"
} > "$OUT/extra-urls.txt"
echo "seeded project $P, task $T, company $C"
