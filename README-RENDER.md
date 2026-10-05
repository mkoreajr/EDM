# EDM Kienyeji Food Shop — Pure Code Final

Final login polish:
- Username and password entered text is normal (400) weight, not bold.
- Password eye control is centered vertically inside the password box.
- Login UI and payment icons are pure HTML/CSS/inline SVG.
- Payment methods: Cash, Mobile Money, Bank only.
- No chicken/photo/screenshot is used.

Login: admin / admin123
Deploy with Render -> New -> Blueprint using render.yaml.

## Public routes
- Customer portal: `/shop`
- Admin entry: `/admin`

The physical portal folders are intentionally named `customer_portal` and `admin_entry` so Apache does not issue a DirectorySlash redirect containing Render's internal port.
