# Setup ngrok for HTTPS Camera Access

## What is ngrok?
ngrok creates a secure HTTPS tunnel to your local server, allowing camera access.

## Setup Steps:

1. **Download ngrok**: Go to https://ngrok.com/download
2. **Extract**: Unzip to a folder (e.g., C:\ngrok)
3. **Sign up**: Create free account at https://ngrok.com
4. **Get auth token**: Copy from your dashboard
5. **Configure**: Run `ngrok authtoken YOUR_TOKEN`

## Usage:

1. **Start your server**: `php server.php` (keep running)
2. **Open new terminal**: Navigate to ngrok folder
3. **Start tunnel**: `ngrok http 8080`
4. **Use HTTPS URL**: ngrok will show something like:
   ```
   https://abc123.ngrok.io -> http://localhost:8080
   ```
5. **Access on phone**: Use the HTTPS ngrok URL

## Benefits:
- ✅ Real HTTPS (camera works)
- ✅ Accessible from anywhere
- ✅ Free tier available
- ✅ No server changes needed