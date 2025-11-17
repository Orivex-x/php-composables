name: Feature Request  
description: Suggest a new feature or enhancement  
title: "[FEATURE] "  
labels: enhancement  
assignees: ''  

body:
- type: markdown  
  attributes:  
  value: |  
  Thank you for suggesting a new feature! Please fill out the details below.  

- type: input  
  id: feature  
  attributes:  
  label: Feature Description  
  placeholder: "Describe the feature you want implemented."  
  required: true  

- type: input  
  id: rationale  
  attributes:  
  label: Why is this feature needed?  
  placeholder: "Explain why this would be useful."  
  required: true  

- type: input  
  id: alternatives  
  attributes:  
  label: Alternatives considered  
  placeholder: "List any alternative solutions or approaches you've considered."  
  required: false  

- type: textarea  
  id: additional  
  attributes:  
  label: Additional context  
  placeholder: "Add any other context or screenshots about the feature request here."  
  required: false  
