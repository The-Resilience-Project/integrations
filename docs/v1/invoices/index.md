# Invoice Endpoints

These endpoints use the Vtiger REST API (`$vtod`) directly, not controllers.

## Overview

| Endpoint | Method | Integration | Purpose |
|---|---|---|---|
| `/Invoices/createInvoice.php` | POST | Vtiger REST + MySQL | Create invoice with student journal and teacher resource line items |
| `/Invoices/createShipment.php` | POST | Vtiger REST + ShipStation | Create ShipStation order from invoice |
| `/Invoices/58850_updateXeroCodeInvoiceItem.php` | POST | Vtiger REST | Sync Xero codes on invoice line items with master product/service data |
| `/Invoices/create_shipment_2025.php` | POST | Vtiger REST + ShipStation | Create ShipStation order (2025/2026 version with store routing) |

## In This Section

- [Invoice Creation](./invoice-creation.md) — Create invoices with student journal and teacher resource line items
- [Shipments](./shipments.md) — Create ShipStation orders from invoices
- [Xero Code Synchronisation](./xero-sync.md) — Sync Xero codes on invoice line items
