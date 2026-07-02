const trimSlash = (value: string) => value.replace(/\/+$/, "");

export const API_BASE_URL = trimSlash(
  process.env.EXPO_PUBLIC_API_BASE_URL || "http://127.0.0.1:8081"
);
