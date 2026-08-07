import requests

def parse_json(response, url):
    try:
        return response.json()
    except:
        print(f"Non-JSON response ({response.status_code}) ({response.url}, {url}):\n{response.text}")
        raise

def whoami(server_url, access_token):
    response = requests.get(
        f"{server_url}/ocs/v2.php/cloud/user",
		#f"https://gs1.local/ocs/v2.php/cloud/user",
        params={"format": "json"},
        headers={
            "Authorization": f"Bearer {access_token}",
            "OCS-APIRequest": "true",
        },
        timeout=30,
    )
    response.raise_for_status()
    return parse_json(response, f"{server_url}/ocs/v2.php/cloud/user")

if __name__ == "__main__":
	whoami('https://portal.local', 'o9KIctyEy0hPfaIROPoH7C1u0z83uvFZbBaxO3u8bycUu4e4WGRFzlDUEIJvSWKjzvdx2Z6D')
