# 🔌 API Documentation

EthioEvent Hub uses internal JSON APIs to power its real-time features.

## 1. Live Search API
**Endpoint**: `api/live_search.php`
**Method**: `GET`

### Parameters:
| Parameter | Type | Description |
| :--- | :--- | :--- |
| `search` | String | (Optional) The search query for title/venue. |
| `category` | Integer | (Optional) Category ID to filter results. |

### Response Example:
```json
[
  {
    "id": 1,
    "title": "Irreecha Festival 2026",
    "venue": "Meskel Square",
    "event_date": "2026-10-05",
    "price": "50.00",
    "category_name": "Cultural"
  }
]
```

---

## 2. Wishlist Toggle API
**Endpoint**: `api/toggle_wishlist.php`
**Method**: `POST` (Requires Session)

### Request Payload:
```json
{
  "event_id": 1
}
```

### Response Example:
```json
{
  "success": true,
  "action": "added"
}
```

---
*© 2026 EthioEvent Hub Team*
