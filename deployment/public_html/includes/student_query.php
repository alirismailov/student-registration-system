<?php
/**
 * Student list search / sort / pagination helpers (Phase 5).
 */

declare(strict_types=1);

const STUDENTS_PER_PAGE = 10;

/**
 * @return array<string, string> key => ORDER BY clause
 */
function student_sort_allowlist(): array
{
    return [
        'newest' => 'created_at DESC, id DESC',
        'oldest' => 'created_at ASC, id ASC',
        'name_asc' => 'name ASC, id ASC',
        'name_desc' => 'name DESC, id DESC',
        'email_asc' => 'email ASC, id ASC',
        'email_desc' => 'email DESC, id DESC',
    ];
}

function resolve_student_sort(?string $sort): string
{
    $allow = student_sort_allowlist();
    $key = is_string($sort) ? $sort : 'newest';

    return array_key_exists($key, $allow) ? $key : 'newest';
}

function escape_like_wildcards(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

/**
 * Build list query parameters from request.
 *
 * @return array{
 *   search: string,
 *   sort: string,
 *   page: int,
 *   per_page: int,
 *   offset: int,
 *   order_sql: string
 * }
 */
function student_list_params(array $query): array
{
    $search = trim((string) ($query['q'] ?? ''));
    if (mb_strlen($search) > 100) {
        $search = mb_substr($search, 0, 100);
    }

    $sort = resolve_student_sort(isset($query['sort']) ? (string) $query['sort'] : null);
    $orderSql = student_sort_allowlist()[$sort];

    $page = 1;
    if (isset($query['page']) && is_string($query['page']) && ctype_digit($query['page'])) {
        $page = max(1, (int) $query['page']);
    }

    $perPage = STUDENTS_PER_PAGE;
    $offset = ($page - 1) * $perPage;

    return [
        'search' => $search,
        'sort' => $sort,
        'page' => $page,
        'per_page' => $perPage,
        'offset' => $offset,
        'order_sql' => $orderSql,
    ];
}

/**
 * @return array{where_sql: string, params: array<string, string>}
 */
function student_search_where(string $search): array
{
    if ($search === '') {
        return ['where_sql' => '', 'params' => []];
    }

    $like = '%' . escape_like_wildcards($search) . '%';

    return [
        'where_sql' => 'WHERE name LIKE :q_name OR email LIKE :q_email OR phone LIKE :q_phone',
        'params' => [
            'q_name' => $like,
            'q_email' => $like,
            'q_phone' => $like,
        ],
    ];
}

/**
 * @param array{search: string, sort: string, page: int, per_page: int, offset: int, order_sql: string} $params
 * @return array{students: list<array<string, mixed>>, total: int, page: int, total_pages: int}
 */
function fetch_students_page(PDO $pdo, array $params): array
{
    $searchBits = student_search_where($params['search']);
    $whereSql = $searchBits['where_sql'];
    $bind = $searchBits['params'];

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM students {$whereSql}");
    $countStmt->execute($bind);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $params['per_page']));
    $page = min($params['page'], $totalPages);
    if ($total === 0) {
        $page = 1;
        $totalPages = 1;
    }
    $offset = ($page - 1) * $params['per_page'];

    $orderSql = $params['order_sql']; // from allowlist only
    $sql = "SELECT id, name, email, phone, address, profile_picture, created_at
            FROM students
            {$whereSql}
            ORDER BY {$orderSql}
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($bind as $key => $value) {
        $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $params['per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll();

    return [
        'students' => $students,
        'total' => $total,
        'page' => $page,
        'total_pages' => $totalPages,
    ];
}

/**
 * Build a view_students.php query string preserving filters.
 */
function student_list_query(array $overrides = []): string
{
    $q = [
        'q' => $overrides['q'] ?? ($_GET['q'] ?? ''),
        'sort' => $overrides['sort'] ?? ($_GET['sort'] ?? 'newest'),
        'page' => $overrides['page'] ?? ($_GET['page'] ?? '1'),
    ];

    if (trim((string) $q['q']) === '') {
        unset($q['q']);
    }
    if ((string) $q['sort'] === 'newest') {
        // keep explicit sort for clarity when not default? keep always for stability
    }
    if ((string) $q['page'] === '1') {
        unset($q['page']);
    }

    return http_build_query($q);
}

