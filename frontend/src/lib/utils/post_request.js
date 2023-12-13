/**
 * Send POST request to a specified endpoint
 *
 * @param {RequestInfo | URL} url
 * @param {object} body
 */
export async function postRequest(url, body) {
  return await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    },
    body: JSON.stringify(body)
  }).then(async res => {
    const json = await res.json();
    if (!res.ok) throw new Error(json.data || json.error || "Unknown error");

    return json;
  });
}
