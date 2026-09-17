// src/types/axios-augment.d.ts
//
// Adds our custom `skipLoading` flag to Axios's request config type.
// Without this, every call site that passes { skipLoading: true }
// (notifications polling, mark-as-read, etc.) fails with:
//   "Object literal may only specify known properties, and
//    'skipLoading' does not exist in type 'AxiosRequestConfig'."
// This doesn't change runtime behavior at all — Axios ignores unknown
// config keys either way — it just tells TypeScript the field is
// expected, project-wide, wherever AxiosRequestConfig is used.
import 'axios'

declare module 'axios' {
  export interface AxiosRequestConfig {
    /** If true, the request/response interceptors in Layout.vue skip
     *  toggling the global loading indicators (startLoading/stopLoading)
     *  for this call — used for silent background requests like
     *  notification polling and read-receipts. */
    skipLoading?: boolean
  }
}