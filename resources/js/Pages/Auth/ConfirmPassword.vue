<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ password: '' });
const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });
</script>
<template>
  <div>
    <Head title="Confirm Password" />
    <h1>Confirm Password</h1>
    <p>This is a secure area of the application.</p>
  </div>
</template>
