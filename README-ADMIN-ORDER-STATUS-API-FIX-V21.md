# Admin Order Status API Fix v21

Status updates now POST to a dedicated JSON-only endpoint. The endpoint authenticates the admin and uses the existing PostgreSQL/PDO transaction. The browser parses the raw response and displays the backend error instead of the generic unexpected-response message.
