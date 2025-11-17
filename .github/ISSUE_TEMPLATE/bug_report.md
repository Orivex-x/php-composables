name: Bug Report  
description: Report a bug or unexpected behavior  
title: "[BUG] "  
labels: bug  
assignees: ''  

body:
- type: markdown  
  attributes:  
  value: |  
  Thanks for taking the time to report a bug! Please fill out the sections below.  

- type: input  
  id: description  
  attributes:  
  label: Describe the bug  
  placeholder: "A clear and concise description of what the bug is."  
  required: true  

- type: input  
  id: steps  
  attributes:  
  label: Steps to reproduce  
  placeholder: "1. Go to ...\n2. Run ...\n3. Observe ..."  
  required: true

- type: input  
  id: expected  
  attributes:  
  label: Expected behavior  
  placeholder: "What you expected to happen."  
  required: true  

- type: input  
  id: actual  
  attributes:  
  label: Actual behavior  
  placeholder: "What actually happened."  
  required: true  

- type: input  
  id: php_version  
  attributes:  
  label: PHP Version  
  placeholder: "e.g., 8.3.6"  
  required: true  

- type: input  
  id: composer_version  
  attributes:  
  label: Composer Version  
  placeholder: "e.g., 2.5.8"  
  required: false  

- type: textarea  
  id: additional  
  attributes:  
  label: Additional context  
  placeholder: "Add any other context about the problem here."  
  required: false  
