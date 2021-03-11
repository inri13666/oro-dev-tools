#### Datagrid Debugger

```bash
php bin/console gorgo:datagrid:debug organization-view-users-grid --bind={\"organization_id\":1}
```
or
```bash
php app/console gorgo:datagrid:debug organization-view-users-grid --bind={\"organization_id\":1}
```

Result:

| Datagrid Name                | Type | Parent             |
|------------------------------|------|--------------------|
| organization-view-users-grid | orm  | user-relation-grid |

```yml
datagrids:
    organization-view-users-grid:
        source:
            type: orm
            query:
                select:
                    - u.id
                    - u.username
                    - u.email
                    - u.firstName
                    - u.lastName
                    - u.enabled
                from:
                    -
                        table: 'OroUserBundle:User'
                        alias: u
                where:
                    and:
                        - ':organization_id MEMBER OF u.organizations'
            bind_parameters:
                - organization_id
        columns:
            firstName:
                label: oro.user.first_name.label
            lastName:
                label: oro.user.last_name.label
            email:
                label: oro.user.email.label
            username:
                label: oro.user.username.label
            enabled:
                label: oro.user.enabled.label
                frontend_type: select
                choices:
                    - Disabled
                    - Enabled
        properties:
            id: null
        sorters:
            columns:
                username:
                    data_name: u.username
                email:
                    data_name: u.email
                firstName:
                    data_name: u.firstName
                lastName:
                    data_name: u.lastName
            disable_default_sorting: true
            default:
                lastName: ASC
        filters:
            columns:
                firstName:
                    type: string
                    data_name: u.firstName
                lastName:
                    type: string
                    data_name: u.lastName
                email:
                    type: string
                    data_name: u.email
                username:
                    type: string
                    data_name: u.username
                enabled:
                    type: boolean
                    data_name: u.enabled
                    options:
                        field_options: { choices: { 2: Disabled, 1: Enabled } }
        name: organization-view-users-grid
        acl_resource: oro_organization_view
        extends: user-relation-grid
```

#### Datagrid Profiler

```bash
php bin/console gorgo:datagrid:profile organization-view-users-grid --current-user=admin --current-organization=1 --bind={\"organization_id\":1}
```
or
```bash
php app/console gorgo:datagrid:profile organization-view-users-grid --current-user=admin --current-organization=1 --bind={\"organization_id\":1}
```

Result:

| Organization | First name | Last name | Primary Email     | Username | Enabled |  Tags            |
|--------------|------------|-----------|-------------------|----------|---------|------------------|
| OroCRM       | John       | Doe       | admin@example.com | admin    | 1       |   ArrayData      |

SQL Query:
```sql
SELECT o0_.id AS id_0, o0_.username AS username_1, o0_.email AS email_2, o0_.first_name AS first_name_3, o0_.last_name AS last_name_4, o0_.enabled AS enabled_5, o1_.id AS id_6, o1_.api_key AS api_key_7, o2_.name AS name_8, o1_.user_id AS user_id_9, o1_.organization_id AS organization_id_10 FROM oro_user o0_ LEFT JOIN oro_user_api o1_ ON (o0_.id = o1_.user_id AND ? = o1_.organization_id) LEFT JOIN oro_organization o2_ ON o0_.organization_id = o2_.id WHERE EXISTS (SELECT 1 FROM oro_user_organization o3_ INNER JOIN oro_organization o4_ ON o3_.organization_id = o4_.id WHERE o3_.user_id = o0_.id AND o4_.id IN (?)) ORDER BY o0_.id ASC LIMIT 25 OFFSET 0
```

SQL Parameters:

| name            | value | type    |
|-----------------|-------|---------|
| organization_id | 1     | integer |
