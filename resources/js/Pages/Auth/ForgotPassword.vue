<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>
<template>
  <div>
    <Head title="Forgot Password" />
    <h1>Forgot Password</h1>
    <p>No problem. Just let us know your email address.</p>
  </div>
</template>
